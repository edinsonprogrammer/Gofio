#!/usr/bin/env node
/**
 * Genera traducciones ES para tokens de iconos FA (cache local).
 * Uso: node scripts/build-fa-token-es.mjs
 */
import fs from 'fs';
import path from 'path';

const TOKEN_FILE = path.resolve('scripts/fa-tokens-all.txt');
const OUT_FILE = path.resolve('scripts/fa-token-es.json');
const DELAY_MS = 120;

const KEEP_AS_IS = new Set([
    '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '20',
    'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
    'alt', 'md', 'lg', 'sm', 'xl', 'csv', 'pdf', 'usd', 'eur', 'gbp', 'jpy', 'cny', 'inr', 'rub', 'btc', 'eth',
    'android', 'apple', 'facebook', 'twitter', 'google', 'github', 'gitlab', 'instagram', 'linkedin', 'youtube', 'whatsapp', 'telegram',
    'linux', 'windows', 'ubuntu', 'reddit', 'tiktok', 'snapchat', 'discord', 'slack', 'spotify', 'paypal', 'amazon', 'microsoft',
]);

const MANUAL = {
    circle: 'circulo',
    arrow: 'flecha',
    up: 'arriba',
    down: 'abajo',
    left: 'izquierda',
    right: 'derecha',
    square: 'cuadrado',
    person: 'persona',
    user: 'usuario',
    users: 'usuarios',
    house: 'casa',
    home: 'inicio',
    heart: 'corazon',
    star: 'estrella',
    check: 'marca',
    xmark: 'equis',
    file: 'archivo',
    folder: 'carpeta',
    lock: 'candado',
    unlock: 'desbloqueo',
    phone: 'telefono',
    envelope: 'sobre',
    bell: 'campana',
    calendar: 'calendario',
    clock: 'reloj',
    camera: 'camara',
    image: 'imagen',
    video: 'video',
    music: 'musica',
    book: 'libro',
    pen: 'pluma',
    pencil: 'lapiz',
    trash: 'basura',
    search: 'buscar',
    magnifying: 'lupa',
    glass: 'lupa',
    plus: 'mas',
    minus: 'menos',
    edit: 'editar',
    save: 'guardar',
    download: 'descargar',
    upload: 'subir',
    share: 'compartir',
    comment: 'comentario',
    comments: 'comentarios',
    thumbs: 'pulgar',
    thumb: 'pulgar',
    bolt: 'rayo',
    fire: 'fuego',
    water: 'agua',
    cloud: 'nube',
    sun: 'sol',
    moon: 'luna',
    snowflake: 'copo-nieve',
    tree: 'arbol',
    leaf: 'hoja',
    seedling: 'brote',
    flower: 'flor',
    bug: 'insecto',
    dog: 'perro',
    cat: 'gato',
    fish: 'pez',
    horse: 'caballo',
    cow: 'vaca',
    bird: 'pajaro',
    car: 'auto',
    truck: 'camion',
    bus: 'bus',
    plane: 'avion',
    ship: 'barco',
    train: 'tren',
    bicycle: 'bicicleta',
    walking: 'caminando',
    running: 'corriendo',
    hospital: 'hospital',
    building: 'edificio',
    school: 'escuela',
    church: 'iglesia',
    store: 'tienda',
    shopping: 'compras',
    cart: 'carrito',
    money: 'dinero',
    dollar: 'dolar',
    credit: 'credito',
    card: 'tarjeta',
    gift: 'regalo',
    trophy: 'trofeo',
    medal: 'medalla',
    award: 'premio',
    crown: 'corona',
    gem: 'gema',
    shield: 'escudo',
    sword: 'espada',
    gun: 'arma',
    bomb: 'bomba',
    key: 'llave',
    wrench: 'llave-inglesa',
    hammer: 'martillo',
    screwdriver: 'destornillador',
    scissors: 'tijeras',
    paint: 'pintura',
    brush: 'brocha',
    palette: 'paleta',
    eye: 'ojo',
    ear: 'oido',
    face: 'cara',
    smile: 'sonrisa',
    grin: 'risa',
    laugh: 'risa',
    sad: 'triste',
    angry: 'enojado',
    tired: 'cansado',
    sick: 'enfermo',
    mask: 'mascara',
    virus: 'virus',
    medical: 'medico',
    hospital: 'hospital',
    pills: 'pastillas',
    syringe: 'jeringa',
    stethoscope: 'estetoscopio',
    wheelchair: 'silla-ruedas',
    baby: 'bebe',
    child: 'nino',
    children: 'ninos',
    man: 'hombre',
    woman: 'mujer',
    male: 'masculino',
    female: 'femenino',
    group: 'grupo',
    people: 'gente',
    hand: 'mano',
    hands: 'manos',
    handshake: 'apreton',
    fist: 'punio',
    peace: 'paz',
    pray: 'rezar',
    food: 'comida',
    pizza: 'pizza',
    burger: 'hamburguesa',
    coffee: 'cafe',
    beer: 'cerveza',
    wine: 'vino',
    apple: 'manzana',
    lemon: 'limon',
    carrot: 'zanahoria',
    bread: 'pan',
    cheese: 'queso',
    egg: 'huevo',
    fish: 'pescado',
    gamepad: 'mando',
    dice: 'dado',
    chess: 'ajedrez',
    king: 'rey',
    queen: 'reina',
    knight: 'caballo-ajedrez',
    pawn: 'peon',
    flag: 'bandera',
    map: 'mapa',
    globe: 'globo',
    earth: 'tierra',
    location: 'ubicacion',
    compass: 'brujula',
    anchor: 'ancla',
    mountain: 'montana',
    beach: 'playa',
    umbrella: 'paraguas',
    tent: 'carpa',
    bed: 'cama',
    bath: 'bano',
    shower: 'ducha',
    toilet: 'inodoro',
    kitchen: 'cocina',
    couch: 'sofa',
    door: 'puerta',
    window: 'ventana',
    lightbulb: 'bombilla',
    plug: 'enchufe',
    battery: 'bateria',
    wifi: 'wifi',
    signal: 'senal',
    satellite: 'satelite',
    robot: 'robot',
    rocket: 'cohete',
    microchip: 'microchip',
    laptop: 'portatil',
    desktop: 'escritorio',
    mobile: 'movil',
    tablet: 'tableta',
    keyboard: 'teclado',
    mouse: 'raton',
    printer: 'impresora',
    database: 'base-datos',
    server: 'servidor',
    code: 'codigo',
    bug: 'error',
    wrench: 'herramienta',
    gear: 'engranaje',
    gears: 'engranajes',
    cogs: 'engranajes',
    cog: 'engranaje',
    filter: 'filtro',
    sort: 'ordenar',
    list: 'lista',
    table: 'tabla',
    chart: 'grafico',
    diagram: 'diagrama',
    percent: 'porcentaje',
    calculator: 'calculadora',
    question: 'pregunta',
    exclamation: 'exclamacion',
    info: 'info',
    warning: 'advertencia',
    ban: 'prohibido',
    slash: 'tachado',
    link: 'enlace',
    unlink: 'desenlace',
    copy: 'copiar',
    paste: 'pegar',
    cut: 'cortar',
    undo: 'deshacer',
    redo: 'rehacer',
    refresh: 'actualizar',
    sync: 'sincronizar',
    power: 'encendido',
    logout: 'salir',
    login: 'entrar',
    user: 'usuario',
    id: 'id',
    tag: 'etiqueta',
    tags: 'etiquetas',
    bookmark: 'marcador',
    pin: 'pin',
    paperclip: 'clip',
    print: 'imprimir',
    fax: 'fax',
    envelope: 'correo',
    inbox: 'bandeja',
    archive: 'archivo',
    trash: 'papelera',
    recycle: 'reciclar',
    legal: 'legal',
    gavel: 'martillo-juez',
    balance: 'balanza',
    scale: 'balanza',
    certificate: 'certificado',
    diploma: 'diploma',
    graduation: 'graduacion',
    language: 'idioma',
    translate: 'traducir',
    volume: 'volumen',
    mute: 'silencio',
    microphone: 'microfono',
    headphones: 'auriculares',
    radio: 'radio',
    tv: 'tele',
    film: 'pelicula',
    photo: 'foto',
    images: 'imagenes',
    portrait: 'retrato',
    crop: 'recortar',
    expand: 'expandir',
    compress: 'comprimir',
    maximize: 'maximizar',
    minimize: 'minimizar',
    close: 'cerrar',
    open: 'abrir',
    play: 'reproducir',
    pause: 'pausa',
    stop: 'detener',
    forward: 'adelante',
    backward: 'atras',
    fast: 'rapido',
    step: 'paso',
    random: 'aleatorio',
    repeat: 'repetir',
    shuffle: 'mezclar',
    quote: 'cita',
    paragraph: 'parrafo',
    heading: 'titulo',
    bold: 'negrita',
    italic: 'cursiva',
    underline: 'subrayado',
    strikethrough: 'tachado',
    align: 'alinear',
    center: 'centro',
    justify: 'justificar',
    indent: 'sangria',
    outdent: 'quitar-sangria',
    eraser: 'borrador',
    highlighter: 'resaltador',
    spell: 'ortografia',
    wifi: 'wifi',
    bluetooth: 'bluetooth',
    qrcode: 'codigo-qr',
    barcode: 'codigo-barras',
    fingerprint: 'huella',
    lock: 'bloqueo',
    unlock: 'desbloquear',
    eye: 'ver',
    eye-slash: 'ocultar',
};

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function translateToken(token) {
    if (KEEP_AS_IS.has(token)) {
        return token;
    }

    if (MANUAL[token]) {
        return MANUAL[token];
    }

    const url = `https://api.mymemory.translated.net/get?q=${encodeURIComponent(token)}&langpair=en|es`;
    const res = await fetch(url);

    if (!res.ok) {
        return token;
    }

    const data = await res.json();
    const translated = data?.responseData?.translatedText?.trim().toLowerCase() ?? token;

    return slugify(translated) || token;
}

function slugify(value) {
    return value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s-]/gi, '')
        .trim()
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
}

async function main() {
    const tokens = fs.readFileSync(TOKEN_FILE, 'utf8').trim().split('\n');
    const existing = fs.existsSync(OUT_FILE) ? JSON.parse(fs.readFileSync(OUT_FILE, 'utf8')) : {};
    const out = { ...existing };

    for (const token of tokens) {
        if (out[token]) {
            continue;
        }

        out[token] = await translateToken(token);
        process.stdout.write(`\r${Object.keys(out).length}/${tokens.length} ${token} -> ${out[token]}`.padEnd(70));
        await sleep(DELAY_MS);
    }

    fs.writeFileSync(OUT_FILE, JSON.stringify(out, null, 2));
    console.log('\nGuardado en', OUT_FILE);
}

main().catch((err) => {
    console.error(err);
    process.exit(1);
});
