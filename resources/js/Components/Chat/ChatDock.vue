<script setup>
/**
 * Dock flotante de mensajería privada con conversaciones y tiempo real.
 */

import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

import { usePage } from '@inertiajs/vue3';

import FaIcon from '@/Components/UI/FaIcon.vue';

import ListRowSkeleton from '@/Components/UI/ListRowSkeleton.vue';



const page = usePage();

const user = computed(() => page.props.auth.user);



const isOpen = ref(false);

const isMinimized = ref(false);

const conversations = ref([]);

const contacts = ref([]);

const activeConversation = ref(null);

const messages = ref([]);

const newMessage = ref('');

const newChatUser = ref('');

const loading = ref(false);

const sending = ref(false);

const pollInterval = ref(null);

const presenceInterval = ref(null);

let echoChannel = null;

let userEchoChannel = null;



const followingIds = computed(() => new Set(contacts.value.map((contact) => contact.id)));



const otherConversations = computed(() =>

    conversations.value.filter((conv) => !followingIds.value.has(conv.other_user?.id)),

);




/** Obtiene las conversaciones recientes del usuario autenticado. */
const loadConversations = async () => {

    if (!user.value) return;



    try {

        const { data } = await window.axios.get('/api/chat/conversations');

        conversations.value = data.data;

    } catch {

        

    }

};




/** Carga la lista de contactos disponibles para iniciar chat. */
const loadContacts = async () => {

    if (!user.value) return;



    try {

        const { data } = await window.axios.get('/api/chat/contacts');

        contacts.value = data.data;

    } catch {

        

    }

};




/** Actualiza conversaciones y contactos tras un evento de red. */
const refreshChatLists = async () => {

    await Promise.all([loadConversations(), loadContacts()]);

};




/** Descarga el historial de mensajes de la conversación activa. */
const loadMessages = async (conversationId) => {

    loading.value = true;

    try {

        const { data } = await window.axios.get(`/api/chat/conversations/${conversationId}/messages`);

        messages.value = data.data;

    } finally {

        loading.value = false;

    }

};




/** Crea un objeto de conversación a partir de un contacto. */
const buildConversationFromContact = (contact) => ({

    id: contact.conversation_id,

    last_message_at: contact.last_message_at,

    other_user: {

        id: contact.id,

        username: contact.username,

        avatar_url: contact.avatar_url,

        is_online: contact.is_online,

    },

});




/** Activa una conversación y carga sus mensajes. */
const selectConversation = async (conv) => {

    activeConversation.value = conv;

    await loadMessages(conv.id);

    subscribeToConversation(conv.id);

};




/** Abre o crea un chat directo con el contacto elegido. */
const openContact = async (contact) => {

    if (contact.conversation_id) {

        const existing = conversations.value.find((conv) => conv.id === contact.conversation_id);

        await selectConversation(existing ?? buildConversationFromContact(contact));

        return;

    }



    try {

        const { data } = await window.axios.post('/api/chat/conversations', {

            username: contact.username,

        });

        await refreshChatLists();

        await selectConversation(data.data);

    } catch (e) {

        alert(e.response?.data?.errors?.username?.[0] || 'No se pudo iniciar el chat.');

    }

};




/** Escucha en tiempo real los mensajes del canal privado. */
const subscribeToConversation = (conversationId) => {

    if (!window.Echo) return;



    if (echoChannel) {

        window.Echo.leave(`chat.${echoChannel}`);

    }



    echoChannel = conversationId;

    window.Echo.private(`chat.${conversationId}`)

        .listen('.message.sent', (data) => {

            if (data.message.user.id !== user.value?.id) {

                messages.value = [...messages.value, data.message];

            }

        });

};




/** Se suscribe al canal de presencia y eventos del usuario. */
const subscribeToUserChannel = () => {

    if (!window.Echo || !user.value?.id || userEchoChannel) return;



    userEchoChannel = user.value.id;

    window.Echo.private(`user.${userEchoChannel}`)

        .listen('.message.sent', (data) => {

            refreshChatLists();



            if (activeConversation.value?.id === data.message.conversation_id) {

                const already = messages.value.some((msg) => msg.id === data.message.id);

                if (!already && data.message.user.id !== user.value?.id) {

                    messages.value = [...messages.value, data.message];

                }

            }

        });

};




/** Envía el texto escrito y lo añade optimistamente al hilo. */
const sendMessage = async () => {

    if (!activeConversation.value || !newMessage.value.trim()) return;



    sending.value = true;

    try {

        const { data } = await window.axios.post(

            `/api/chat/conversations/${activeConversation.value.id}/messages`,

            { body: newMessage.value },

        );

        messages.value = [...messages.value, data.data];

        newMessage.value = '';

        await refreshChatLists();

    } finally {

        sending.value = false;

    }

};




/** Inicia una nueva conversación con el usuario indicado. */
const startChat = async () => {

    if (!newChatUser.value.trim()) return;



    try {

        const { data } = await window.axios.post('/api/chat/conversations', {

            username: newChatUser.value.trim(),

        });

        await refreshChatLists();

        await selectConversation(data.data);

        newChatUser.value = '';

    } catch (e) {

        alert(e.response?.data?.errors?.username?.[0] || 'No se pudo iniciar el chat.');

    }

};




/** Informa al servidor que el usuario está activo en el chat. */
const pingPresence = async () => {

    if (!user.value) return;



    try {

        await window.axios.post('/api/chat/presence');

    } catch {

        

    }

};




/** Alterna la visibilidad o el estado activo del componente. */
const toggle = () => {

    isOpen.value = !isOpen.value;

    isMinimized.value = false;

    if (isOpen.value) {

        refreshChatLists();

        pingPresence();

    }

};




/** Abre el panel o modal correspondiente. */
const open = () => {

    isOpen.value = true;

    isMinimized.value = false;

    refreshChatLists();

    pingPresence();

};



defineExpose({ open, toggle });




/** Formatea la marca temporal en texto relativo legible. */
const formatTime = (iso) => {

    if (!iso) return '';

    return new Date(iso).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });

};




/** Actualiza la lista de contactos cuando cambia un seguimiento. */
const onFollowingChanged = () => {

    if (isOpen.value) {

        refreshChatLists();

    }

};



onMounted(() => {

    if (!user.value) return;



    refreshChatLists();

    subscribeToUserChannel();



    pollInterval.value = setInterval(() => {

        if (isOpen.value) {

            refreshChatLists();

            if (activeConversation.value) {

                loadMessages(activeConversation.value.id);

            }

        }

    }, 15000);



    presenceInterval.value = setInterval(() => {

        if (isOpen.value) {

            pingPresence();

        }

    }, 90000);



    window.addEventListener('gofio:following-changed', onFollowingChanged);

});



onUnmounted(() => {

    if (pollInterval.value) clearInterval(pollInterval.value);

    if (presenceInterval.value) clearInterval(presenceInterval.value);

    window.removeEventListener('gofio:following-changed', onFollowingChanged);



    if (echoChannel && window.Echo) {

        window.Echo.leave(`chat.${echoChannel}`);

    }



    if (userEchoChannel && window.Echo) {

        window.Echo.leave(`user.${userEchoChannel}`);

    }

});



watch(isOpen, (open) => {

    if (open) {

        refreshChatLists();

        pingPresence();

    }

});



watch(user, (nextUser) => {

    if (nextUser) {

        refreshChatLists();

        subscribeToUserChannel();

    }

});
</script>



<template>
    <!-- Dock flotante de mensajería con lista de conversaciones y chat activo -->


    <div

        v-if="user"

        class="chat-dock-root"

    >

        <div

            v-if="isOpen && !isMinimized"

            class="chat-dock-panel gofio-box"

        >

            <div class="chat-dock-header">

                <div class="chat-dock-header__title">

                    <FaIcon icon="fa-solid fa-comment-dots" class="text-sm" />

                    <span>

                        {{ activeConversation ? activeConversation.other_user?.username : 'Mensajes' }}

                    </span>

                    <FaIcon

                        v-if="activeConversation?.other_user"

                        icon="fa-solid fa-lightbulb"

                        class="chat-dock-status-bulb"

                        :class="activeConversation.other_user.is_online

                            ? 'chat-dock-status-bulb--online'

                            : 'chat-dock-status-bulb--offline'"

                        :title="activeConversation.other_user.is_online ? 'En línea' : 'Desconectado'"

                    />

                </div>

                <div class="chat-dock-header__actions">

                    <button

                        type="button"

                        class="chat-dock-header__btn"

                        aria-label="Minimizar chat"

                        @click="isMinimized = true"

                    >

                        <FaIcon icon="fa-solid fa-minus" />

                    </button>

                    <button

                        type="button"

                        class="chat-dock-header__btn"

                        aria-label="Cerrar chat"

                        @click="isOpen = false"

                    >

                        <FaIcon icon="fa-solid fa-xmark" />

                    </button>

                </div>

            </div>



            <div v-if="!activeConversation" class="chat-dock-body">

                <div class="chat-dock-search">

                    <input

                        v-model="newChatUser"

                        type="text"

                        placeholder="Buscar usuario..."

                        class="gofio-input chat-dock-search__input"

                        @keyup.enter="startChat"

                    />

                    <button type="button" class="gofio-btn-primary chat-dock-search__btn" @click="startChat">

                        <FaIcon icon="fa-solid fa-plus" />

                    </button>

                </div>



                <div class="chat-dock-list">

                    <p class="chat-dock-list__heading">Siguiendo</p>

                    <ul v-if="contacts.length > 0">

                        <li

                            v-for="contact in contacts"

                            :key="contact.id"

                            class="chat-dock-row"

                            @click="openContact(contact)"

                        >

                            <FaIcon

                                icon="fa-solid fa-lightbulb"

                                class="chat-dock-status-bulb"

                                :class="contact.is_online

                                    ? 'chat-dock-status-bulb--online'

                                    : 'chat-dock-status-bulb--offline'"

                                :title="contact.is_online ? 'En línea' : 'Desconectado'"

                            />

                            <div class="chat-dock-row__meta">

                                <p class="chat-dock-row__name">{{ contact.username }}</p>

                                <p class="chat-dock-row__hint">

                                    {{ contact.conversation_id ? formatTime(contact.last_message_at) : 'Iniciar chat' }}

                                </p>

                            </div>

                        </li>

                    </ul>

                    <p v-else class="chat-dock-empty">Sigue a alguien para chatear desde aquí.</p>



                    <template v-if="otherConversations.length > 0">

                        <p class="chat-dock-list__heading chat-dock-list__heading--spaced">Otros chats</p>

                        <ul>

                            <li

                                v-for="conv in otherConversations"

                                :key="conv.id"

                                class="chat-dock-row"

                                @click="selectConversation(conv)"

                            >

                                <FaIcon

                                    icon="fa-solid fa-lightbulb"

                                    class="chat-dock-status-bulb"

                                    :class="conv.other_user?.is_online

                                        ? 'chat-dock-status-bulb--online'

                                        : 'chat-dock-status-bulb--offline'"

                                />

                                <div class="chat-dock-row__meta">

                                    <p class="chat-dock-row__name">{{ conv.other_user?.username }}</p>

                                    <p class="chat-dock-row__hint">{{ formatTime(conv.last_message_at) }}</p>

                                </div>

                            </li>

                        </ul>

                    </template>

                </div>

            </div>



            <div v-else class="chat-dock-body chat-dock-body--thread">

                <button

                    type="button"

                    class="chat-dock-back"

                    @click="activeConversation = null"

                >

                    <FaIcon icon="fa-solid fa-arrow-left" class="mr-1 text-[10px]" />

                    Volver

                </button>



                <div class="chat-dock-messages">

                    <ListRowSkeleton v-if="loading" :count="4" />

                    <div

                        v-for="msg in messages"

                        :key="msg.id"

                        class="chat-dock-message"

                        :class="msg.user.id === user?.id ? 'chat-dock-message--mine' : 'chat-dock-message--theirs'"

                    >

                        <div class="chat-dock-message__bubble">

                            <p>{{ msg.body }}</p>

                            <p class="chat-dock-message__time">{{ formatTime(msg.created_at) }}</p>

                        </div>

                    </div>

                </div>



    <!-- Formulario principal -->
                <form class="chat-dock-compose" @submit.prevent="sendMessage">

                    <input

                        v-model="newMessage"

                        type="text"

                        placeholder="Escribe un mensaje..."

                        class="gofio-input chat-dock-compose__input"

                    />

                    <button type="submit" class="gofio-btn-primary chat-dock-compose__btn" :disabled="sending">

                        <FaIcon icon="fa-solid fa-paper-plane" />

                    </button>

                </form>

            </div>

        </div>



        <button

            type="button"

            class="chat-dock-toggle gofio-box"

            @click="toggle"

        >

            <FaIcon icon="fa-solid fa-comment-dots" class="text-brandColor" />

            <span>Chat</span>

        </button>

    </div>

</template>


