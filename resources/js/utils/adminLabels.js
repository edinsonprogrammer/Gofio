/**

 * Etiquetas en español para métricas, acciones y estados del panel de administración.

 * Centraliza los textos mostrados en tablas, filtros y registros de auditoría.

 */



/** Textos de las tarjetas de estadísticas del dashboard administrativo. */

export const statLabels = {

    users_total: 'Usuarios totales',

    users_banned: 'Usuarios suspendidos',

    posts_total: 'Publicaciones totales',

    posts_banned: 'Publicaciones baneadas',

    posts_featured: 'Publicaciones destacadas',

    comments_total: 'Comentarios totales',

    reports_pending: 'Denuncias pendientes',

    alerts_open: 'Alertas abiertas',

    verifications_pending: 'Verificaciones pendientes',

    tickets_open: 'Tickets abiertos',

};



/** Nombres legibles de las acciones registradas en el log de moderación. */

export const adminActionLabels = {

    ban_user: 'Suspender usuario',

    unban_user: 'Reactivar usuario',

    update_user: 'Actualizar usuario',

    revoke_verification: 'Revocar verificación',

    revoke_creator_plus: 'Retirar Creator Plus',

    ticket_update: 'Actualizar ticket',

    create_rank: 'Crear rango',

    update_rank: 'Actualizar rango',

    delete_rank: 'Eliminar rango',

    assign_rank: 'Asignar rango',

    unlock_rank: 'Desbloquear rango auto',

    approve_verification: 'Aprobar verificación',

    reject_verification: 'Rechazar verificación',

    update_settings: 'Actualizar configuración',

    update_tip_fee: 'Actualizar comisión de propinas',

    register_coin_deposit: 'Registrar depósito de monedas',

    ban_post: 'Banear publicación',

    publish_post: 'Publicar',

    post_status_published: 'Publicar publicación',

    post_status_banned: 'Banear publicación',

    post_status_draft: 'Guardar como borrador',

    feature_post: 'Destacar publicación',

    unfeature_post: 'Quitar destacado',

    sticky_post: 'Fijar publicación',

    unsticky_post: 'Desfijar publicación',

    grant_medal: 'Otorgar medalla',

    grant_award: 'Otorgar premio',

    create_category: 'Crear categoría',
    update_category: 'Actualizar categoría',
    delete_category: 'Eliminar categoría',
    create_badword: 'Añadir palabra prohibida',
    delete_badword: 'Eliminar palabra prohibida',
    bulk_approve_verifications: 'Aprobar verificaciones en lote',
    bulk_reject_verifications: 'Rechazar verificaciones en lote',

    report_reviewed: 'Marcar denuncia como revisada',

    report_dismissed: 'Descartar denuncia',

    report_action_dismiss: 'Descartar denuncia',

    report_action_reviewed: 'Revisar denuncia',

    report_action_delete_content: 'Eliminar contenido denunciado',

    report_action_suspend_author: 'Suspender autor denunciado',

    report_edit_content: 'Editar contenido denunciado',

    alert_resolved: 'Resolver alerta de moderación',

    alert_dismissed: 'Descartar alerta de moderación',

    update_vidu_ads_settings: 'Actualizar publicidad Vidu',

    upload_vidu_ad_creative: 'Subir anuncio Vidu',

    activate_vidu_ad_creative: 'Activar anuncio Vidu',

    delete_vidu_ad_creative: 'Eliminar anuncio Vidu',

    bulk_vidu_ads_videos: 'Acción masiva publicidad Vidu',

    upload_vidu_ad_banner: 'Subir banner Vidu',

    delete_vidu_ad_banner: 'Eliminar banner Vidu',

};



/** Traducción del tipo de entidad afectada en acciones administrativas. */

export const targetTypeLabels = {

    user: 'usuario',

    post: 'publicación',

    comment: 'comentario',

    ticket: 'ticket',

    rank: 'rango',

    settings: 'configuración',

    verification: 'verificación',

    medal: 'medalla',

    award: 'premio',

    theme: 'tema',

    category: 'categoría',
    badword: 'palabra prohibida',

    report: 'denuncia',

    alert: 'alerta',

    wallet: 'monedero',

    vidu_ad: 'publicidad Vidu',

};



/** Estados posibles de una publicación en el listado admin. */

export const postStatusLabels = {

    published: 'Publicado',

    banned: 'Baneado',

    draft: 'Borrador',

};



/** Estados del ciclo de vida de un ticket de soporte. */

export const ticketStatusLabels = {

    open: 'Abierto',

    in_progress: 'En progreso',

    closed: 'Cerrado',

};



/** Acciones aplicables a palabras prohibidas detectadas en contenido. */

export const badWordActionLabels = {

    filter: 'Filtrar',

    block: 'Bloquear',

};



/** Condiciones de desbloqueo automático de medallas. */

export const medalConditionLabels = {

    manual: 'Manual',

    karma: 'Karma',

    posts: 'Publicaciones',

    comments: 'Comentarios',

    verified: 'Verificado',

};



/** Niveles de verificación mostrados en perfiles y listados. */

export const verificationLabels = {

    none: 'Sin verificar',

    user_verified: 'Verificado',

    creator_plus: 'Creator Plus',

};



/** Tipos de contenido denunciable en el sistema de reportes. */

export const reportableTypeLabels = {

    Post: 'publicación',

    Comment: 'comentario',

    User: 'usuario',

};



/**

 * Obtiene la etiqueta de un mapa o devuelve la clave como respaldo.

 * @param {object} map - Diccionario de etiquetas.

 * @param {string} key - Clave a traducir.

 * @param {string} fallback - Valor por defecto si no existe traducción.

 * @returns {string} Texto legible para la interfaz.

 */

export const label = (map, key, fallback = key) => map[key] ?? fallback;



/**

 * Traduce una acción del historial administrativo al español.

 * @param {string} action - Clave técnica guardada en admin_action_logs.

 * @returns {string} Descripción legible de la acción.

 */

export const translateAdminAction = (action) => {

    if (!action) {

        return 'Acción desconocida';

    }



    if (adminActionLabels[action]) {

        return adminActionLabels[action];

    }



    // Respaldo legible para claves nuevas aún no mapeadas.

    return action

        .replace(/_/g, ' ')

        .replace(/\b\w/g, (char) => char.toUpperCase());

};



/**

 * Formatea el destino de una acción administrativa (tipo + identificador).

 * @param {string|null} targetType - Tipo de entidad afectada.

 * @param {number|string|null} targetId - ID del recurso, si aplica.

 * @returns {string|null} Texto descriptivo o null si no hay destino.

 */

export const formatAdminLogTarget = (targetType, targetId) => {

    if (!targetType) {

        return null;

    }



    const typeLabel = label(targetTypeLabels, targetType, targetType);



    if (targetId === null || targetId === undefined || targetId === '') {

        return typeLabel;

    }



    return `${typeLabel} #${targetId}`;

};


