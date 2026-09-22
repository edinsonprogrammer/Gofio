/**
 * Catálogo de países con códigos ISO y utilidades para banderas emoji,
 * búsqueda y resolución a partir de nombre o código.
 */

/**
 * Convierte un código ISO de dos letras en su bandera emoji regional.
 * @param {string} code - Código de país (ej. AR, ES).
 * @returns {string} Emoji de bandera o cadena vacía si el código es inválido.
 */
export function countryFlag(code) {
    if (!code || code.length !== 2) {
        return '';
    }

    return code
        .toUpperCase()
        .replace(/./g, (char) => String.fromCodePoint(127397 + char.charCodeAt(0)));
}

/** Lista completa de países disponibles en el selector de perfil. */
export const COUNTRIES = [
    { code: 'AR', name: 'Argentina' },
    { code: 'BO', name: 'Bolivia' },
    { code: 'BR', name: 'Brasil' },
    { code: 'CL', name: 'Chile' },
    { code: 'CO', name: 'Colombia' },
    { code: 'CR', name: 'Costa Rica' },
    { code: 'CU', name: 'Cuba' },
    { code: 'DO', name: 'República Dominicana' },
    { code: 'EC', name: 'Ecuador' },
    { code: 'SV', name: 'El Salvador' },
    { code: 'GT', name: 'Guatemala' },
    { code: 'HN', name: 'Honduras' },
    { code: 'MX', name: 'México' },
    { code: 'NI', name: 'Nicaragua' },
    { code: 'PA', name: 'Panamá' },
    { code: 'PY', name: 'Paraguay' },
    { code: 'PE', name: 'Perú' },
    { code: 'PR', name: 'Puerto Rico' },
    { code: 'UY', name: 'Uruguay' },
    { code: 'VE', name: 'Venezuela' },
    { code: 'ES', name: 'España' },
    { code: 'US', name: 'Estados Unidos' },
    { code: 'CA', name: 'Canadá' },
    { code: 'GB', name: 'Reino Unido' },
    { code: 'FR', name: 'Francia' },
    { code: 'DE', name: 'Alemania' },
    { code: 'IT', name: 'Italia' },
    { code: 'PT', name: 'Portugal' },
    { code: 'NL', name: 'Países Bajos' },
    { code: 'BE', name: 'Bélgica' },
    { code: 'CH', name: 'Suiza' },
    { code: 'AT', name: 'Austria' },
    { code: 'SE', name: 'Suecia' },
    { code: 'NO', name: 'Noruega' },
    { code: 'DK', name: 'Dinamarca' },
    { code: 'FI', name: 'Finlandia' },
    { code: 'IE', name: 'Irlanda' },
    { code: 'PL', name: 'Polonia' },
    { code: 'CZ', name: 'República Checa' },
    { code: 'GR', name: 'Grecia' },
    { code: 'TR', name: 'Turquía' },
    { code: 'RU', name: 'Rusia' },
    { code: 'UA', name: 'Ucrania' },
    { code: 'CN', name: 'China' },
    { code: 'JP', name: 'Japón' },
    { code: 'KR', name: 'Corea del Sur' },
    { code: 'IN', name: 'India' },
    { code: 'AU', name: 'Australia' },
    { code: 'NZ', name: 'Nueva Zelanda' },
    { code: 'ZA', name: 'Sudáfrica' },
    { code: 'EG', name: 'Egipto' },
    { code: 'MA', name: 'Marruecos' },
    { code: 'NG', name: 'Nigeria' },
    { code: 'KE', name: 'Kenia' },
    { code: 'IL', name: 'Israel' },
    { code: 'SA', name: 'Arabia Saudita' },
    { code: 'AE', name: 'Emiratos Árabes Unidos' },
    { code: 'QA', name: 'Catar' },
    { code: 'PH', name: 'Filipinas' },
    { code: 'TH', name: 'Tailandia' },
    { code: 'VN', name: 'Vietnam' },
    { code: 'ID', name: 'Indonesia' },
    { code: 'MY', name: 'Malasia' },
    { code: 'SG', name: 'Singapur' },
    { code: 'PK', name: 'Pakistán' },
    { code: 'BD', name: 'Bangladesh' },
    { code: 'RO', name: 'Rumania' },
    { code: 'HU', name: 'Hungría' },
    { code: 'HR', name: 'Croacia' },
    { code: 'RS', name: 'Serbia' },
    { code: 'BG', name: 'Bulgaria' },
    { code: 'SK', name: 'Eslovaquia' },
    { code: 'SI', name: 'Eslovenia' },
    { code: 'LT', name: 'Lituania' },
    { code: 'LV', name: 'Letonia' },
    { code: 'EE', name: 'Estonia' },
    { code: 'IS', name: 'Islandia' },
    { code: 'LU', name: 'Luxemburgo' },
    { code: 'MT', name: 'Malta' },
    { code: 'CY', name: 'Chipre' },
    { code: 'AD', name: 'Andorra' },
    { code: 'MC', name: 'Mónaco' },
    { code: 'SM', name: 'San Marino' },
    { code: 'VA', name: 'Ciudad del Vaticano' },
    { code: 'HT', name: 'Haití' },
    { code: 'JM', name: 'Jamaica' },
    { code: 'TT', name: 'Trinidad y Tobago' },
    { code: 'BZ', name: 'Belice' },
    { code: 'GY', name: 'Guyana' },
    { code: 'SR', name: 'Surinam' },
    { code: 'GF', name: 'Guayana Francesa' },
    { code: 'FK', name: 'Islas Malvinas' },
    { code: 'GL', name: 'Groenlandia' },
    { code: 'BM', name: 'Bermudas' },
    { code: 'BS', name: 'Bahamas' },
    { code: 'BB', name: 'Barbados' },
    { code: 'LC', name: 'Santa Lucía' },
    { code: 'GD', name: 'Granada' },
    { code: 'VC', name: 'San Vicente y las Granadinas' },
    { code: 'AG', name: 'Antigua y Barbuda' },
    { code: 'DM', name: 'Dominica' },
    { code: 'KN', name: 'San Cristóbal y Nieves' },
    { code: 'AW', name: 'Aruba' },
    { code: 'CW', name: 'Curazao' },
    { code: 'GP', name: 'Guadalupe' },
    { code: 'MQ', name: 'Martinica' },
    { code: 'KY', name: 'Islas Caimán' },
    { code: 'VG', name: 'Islas Vírgenes Británicas' },
    { code: 'VI', name: 'Islas Vírgenes de EE.UU.' },
    { code: 'TC', name: 'Islas Turcas y Caicos' },
    { code: 'AI', name: 'Anguila' },
    { code: 'MS', name: 'Montserrat' },
    { code: 'SX', name: 'Sint Maarten' },
    { code: 'BQ', name: 'Caribe Neerlandés' },
    { code: 'BL', name: 'San Bartolomé' },
    { code: 'MF', name: 'San Martín' },
    { code: 'PM', name: 'San Pedro y Miquelón' },
    { code: 'FO', name: 'Islas Feroe' },
    { code: 'GI', name: 'Gibraltar' },
    { code: 'GG', name: 'Guernsey' },
    { code: 'JE', name: 'Jersey' },
    { code: 'IM', name: 'Isla de Man' },
    { code: 'AX', name: 'Islas Åland' },
    { code: 'SJ', name: 'Svalbard y Jan Mayen' },
    { code: 'AL', name: 'Albania' },
    { code: 'BA', name: 'Bosnia y Herzegovina' },
    { code: 'ME', name: 'Montenegro' },
    { code: 'MK', name: 'Macedonia del Norte' },
    { code: 'MD', name: 'Moldavia' },
    { code: 'BY', name: 'Bielorrusia' },
    { code: 'GE', name: 'Georgia' },
    { code: 'AM', name: 'Armenia' },
    { code: 'AZ', name: 'Azerbaiyán' },
    { code: 'KZ', name: 'Kazajistán' },
    { code: 'UZ', name: 'Uzbekistán' },
    { code: 'TM', name: 'Turkmenistán' },
    { code: 'KG', name: 'Kirguistán' },
    { code: 'TJ', name: 'Tayikistán' },
    { code: 'MN', name: 'Mongolia' },
    { code: 'KP', name: 'Corea del Norte' },
    { code: 'TW', name: 'Taiwán' },
    { code: 'HK', name: 'Hong Kong' },
    { code: 'MO', name: 'Macao' },
    { code: 'LA', name: 'Laos' },
    { code: 'KH', name: 'Camboya' },
    { code: 'MM', name: 'Myanmar' },
    { code: 'BN', name: 'Brunéi' },
    { code: 'TL', name: 'Timor Oriental' },
    { code: 'NP', name: 'Nepal' },
    { code: 'LK', name: 'Sri Lanka' },
    { code: 'MV', name: 'Maldivas' },
    { code: 'BT', name: 'Bután' },
    { code: 'AF', name: 'Afganistán' },
    { code: 'IR', name: 'Irán' },
    { code: 'IQ', name: 'Irak' },
    { code: 'SY', name: 'Siria' },
    { code: 'LB', name: 'Líbano' },
    { code: 'JO', name: 'Jordania' },
    { code: 'PS', name: 'Palestina' },
    { code: 'KW', name: 'Kuwait' },
    { code: 'BH', name: 'Baréin' },
    { code: 'OM', name: 'Omán' },
    { code: 'YE', name: 'Yemen' },
    { code: 'LY', name: 'Libia' },
    { code: 'TN', name: 'Túnez' },
    { code: 'DZ', name: 'Argelia' },
    { code: 'SD', name: 'Sudán' },
    { code: 'SS', name: 'Sudán del Sur' },
    { code: 'ET', name: 'Etiopía' },
    { code: 'SO', name: 'Somalia' },
    { code: 'DJ', name: 'Yibuti' },
    { code: 'ER', name: 'Eritrea' },
    { code: 'UG', name: 'Uganda' },
    { code: 'RW', name: 'Ruanda' },
    { code: 'BI', name: 'Burundi' },
    { code: 'TZ', name: 'Tanzania' },
    { code: 'MZ', name: 'Mozambique' },
    { code: 'ZW', name: 'Zimbabue' },
    { code: 'ZM', name: 'Zambia' },
    { code: 'MW', name: 'Malaui' },
    { code: 'AO', name: 'Angola' },
    { code: 'NA', name: 'Namibia' },
    { code: 'BW', name: 'Botsuana' },
    { code: 'LS', name: 'Lesoto' },
    { code: 'SZ', name: 'Esuatini' },
    { code: 'MG', name: 'Madagascar' },
    { code: 'MU', name: 'Mauricio' },
    { code: 'SC', name: 'Seychelles' },
    { code: 'KM', name: 'Comoras' },
    { code: 'CV', name: 'Cabo Verde' },
    { code: 'GM', name: 'Gambia' },
    { code: 'SN', name: 'Senegal' },
    { code: 'MR', name: 'Mauritania' },
    { code: 'ML', name: 'Mali' },
    { code: 'BF', name: 'Burkina Faso' },
    { code: 'NE', name: 'Níger' },
    { code: 'TD', name: 'Chad' },
    { code: 'CM', name: 'Camerún' },
    { code: 'CF', name: 'República Centroafricana' },
    { code: 'GQ', name: 'Guinea Ecuatorial' },
    { code: 'GA', name: 'Gabón' },
    { code: 'CG', name: 'Congo' },
    { code: 'CD', name: 'República Democrática del Congo' },
    { code: 'ST', name: 'Santo Tomé y Príncipe' },
    { code: 'GH', name: 'Ghana' },
    { code: 'TG', name: 'Togo' },
    { code: 'BJ', name: 'Benín' },
    { code: 'CI', name: 'Costa de Marfil' },
    { code: 'LR', name: 'Liberia' },
    { code: 'SL', name: 'Sierra Leona' },
    { code: 'GN', name: 'Guinea' },
    { code: 'GW', name: 'Guinea-Bisáu' },
    { code: 'FJ', name: 'Fiyi' },
    { code: 'PG', name: 'Papúa Nueva Guinea' },
    { code: 'SB', name: 'Islas Salomón' },
    { code: 'VU', name: 'Vanuatu' },
    { code: 'NC', name: 'Nueva Caledonia' },
    { code: 'PF', name: 'Polinesia Francesa' },
    { code: 'WS', name: 'Samoa' },
    { code: 'TO', name: 'Tonga' },
    { code: 'KI', name: 'Kiribati' },
    { code: 'FM', name: 'Micronesia' },
    { code: 'MH', name: 'Islas Marshall' },
    { code: 'PW', name: 'Palaos' },
    { code: 'NR', name: 'Nauru' },
    { code: 'TV', name: 'Tuvalu' },
];

/**
 * Busca un país por su código ISO de dos letras.
 * @param {string} code - Código de país.
 * @returns {object|null} Objeto { code, name } o null.
 */
export function findCountryByCode(code) {
    return COUNTRIES.find((c) => c.code === code?.toUpperCase()) ?? null;
}

/**
 * Busca un país por nombre exacto (insensible a mayúsculas).
 * @param {string} name - Nombre del país.
 * @returns {object|null} Objeto { code, name } o null.
 */
export function findCountryByName(name) {
    const q = name?.trim().toLowerCase();
    if (!q) {
        return null;
    }

    return COUNTRIES.find((c) => c.name.toLowerCase() === q) ?? null;
}

/**
 * Resuelve la bandera emoji priorizando código ISO y buscando por nombre como respaldo.
 * @param {string} country - Nombre del país guardado en perfil.
 * @param {string} countryCode - Código ISO alternativo.
 * @returns {string} Emoji de bandera o cadena vacía.
 */
export function resolveCountryFlag(country, countryCode) {
    if (countryCode) {
        return countryFlag(countryCode);
    }

    const found = findCountryByName(country);
    return found ? countryFlag(found.code) : '';
}

/**
 * Filtra países por nombre o código para el autocompletado del selector.
 * @param {string} query - Texto de búsqueda del usuario.
 * @returns {object[]} Hasta 12 coincidencias.
 */
export function filterCountries(query) {
    const q = query.trim().toLowerCase();
    if (!q) {
        return COUNTRIES.slice(0, 12);
    }

    return COUNTRIES.filter(
        (c) => c.name.toLowerCase().includes(q) || c.code.toLowerCase().includes(q),
    ).slice(0, 12);
}

