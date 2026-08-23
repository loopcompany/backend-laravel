export const FONT_OPTIONS = [
    { label: 'پیش فرض', value: '' },
    { label: 'لوپ (Vazirmatn UI FD NL)', value: 'loop_card' },
    { label: 'وزیر', value: 'vazir' },
    { label: 'ایران سنس', value: 'iransans' },
    { label: 'یکان', value: 'yekan' },
    { label: 'تنها', value: 'tanha' },
    { label: 'سینا', value: 'sina' },
    { label: 'کودک', value: 'KoodakB' },
    { label: 'خودکار', value: 'Khodkar' },
];

export const ALIGN_OPTIONS = [
    { label: 'چپ', value: 'left' },
    { label: 'وسط', value: 'center' },
    { label: 'راست', value: 'right' },
];

export const ALLOWED_FONT_FAMILIES = {
    loop_card: "'LoopCardFont', sans-serif",
    vazir: "'VazirFont', sans-serif",
    iransans: "'IRANSansFont', sans-serif",
    yekan: "'YekanFont', sans-serif",
    tanha: "'TanhaFont', sans-serif",
    sina: "'BSinaBd', sans-serif",
    KoodakB: "'KoodakB', sans-serif",
    Khodkar: "'Khodkar', sans-serif",
};

export const BLOCK_TYPOGRAPHY_FIELDS = {
    text: [
        { key: 'description_font', label: 'فونت توضیحات', type: 'font' },
        { key: 'description_size', label: 'اندازه متن', type: 'size' },
        { key: 'description_align', label: 'تراز متن', type: 'align' },
    ],
    link: [
        { key: 'title_font', label: 'فونت عنوان لینک', type: 'font' },
    ],
    social: [
        { key: 'title_font', label: 'فونت عنوان لینک', type: 'font' },
    ],
    map: [
        { key: 'title_font', label: 'فونت عنوان', type: 'font' },
        { key: 'address_font', label: 'فونت آدرس', type: 'font' },
        { key: 'button_font', label: 'فونت دکمه', type: 'font' },
    ],
    faq: [
        { key: 'title_font', label: 'فونت عنوان', type: 'font' },
        { key: 'faq_question_font', label: 'فونت سوال', type: 'font' },
        { key: 'faq_answer_font', label: 'فونت پاسخ', type: 'font' },
    ],
    gallery: [
        { key: 'title_font', label: 'فونت عنوان', type: 'font' },
        { key: 'caption_font', label: 'فونت کپشن', type: 'font' },
    ],
};

export function normalizeTypography(input) {
    if (!input || typeof input !== 'object') {
        return {};
    }

    return Object.entries(input).reduce((acc, [key, value]) => {
        // Handle size fields (numbers)
        if (key.endsWith('_size')) {
            const numericSize = typeof value === 'number' ? value : parseFloat(String(value).trim());
            if (Number.isFinite(numericSize) && numericSize >= 10 && numericSize <= 72) {
                acc[key] = numericSize;
            }
            return acc;
        }

        // Handle alignment fields (strings)
        if (key.endsWith('_align')) {
            if (typeof value === 'string') {
                const normalized = value.trim();
                const allowedAlignments = ['left', 'center', 'right'];
                if (allowedAlignments.includes(normalized)) {
                    acc[key] = normalized;
                }
            }
            return acc;
        }

        // Handle font fields (strings)
        if (key.endsWith('_font')) {
            if (typeof value === 'string') {
                const normalized = value.trim();
                if (normalized && ALLOWED_FONT_FAMILIES[normalized]) {
                    acc[key] = normalized;
                }
            }
            return acc;
        }

        return acc;
    }, {});
}

export function getFontFamily(fontKey) {
    if (!fontKey || typeof fontKey !== 'string') {
        return null;
    }

    return ALLOWED_FONT_FAMILIES[fontKey] || null;
}

export function getTypographyStyle(typography, fieldKey) {
    const normalized = normalizeTypography(typography);
    const fontKey = normalized[fieldKey] || null;
    const fontFamily = getFontFamily(fontKey);

    const style = {};

    if (fontFamily) {
        style.fontFamily = fontFamily;
    }

    // Handle size and alignment for description_font
    if (fieldKey === 'description_font') {
        // Handle description_size
        const sizeKey = 'description_size';
        if (sizeKey in normalized) {
            const sizeValue = normalized[sizeKey];
            // Sanitize: must be numeric only
            const numericSize = typeof sizeValue === 'number' ? sizeValue : parseFloat(String(sizeValue).trim());
            
            if (Number.isFinite(numericSize) && numericSize >= 10 && numericSize <= 72) {
                style.fontSize = `${numericSize}px`;
            }
        }

        // Handle description_align
        const alignKey = 'description_align';
        if (alignKey in normalized) {
            const alignValue = normalized[alignKey];
            const allowedAlignments = ['left', 'center', 'right'];
            
            if (typeof alignValue === 'string' && allowedAlignments.includes(alignValue.trim())) {
                style.textAlign = alignValue.trim();
            }
        }
    }

    return style;
}

export function getTypographyFieldsByType(type) {
    return BLOCK_TYPOGRAPHY_FIELDS[type] || [];
}