export function normalizeCssColor(value) {
  if (!value) return undefined

  if (typeof value !== 'string') return undefined

  const color = value.trim()

  if (!color) return undefined

  if (color === 'transparent') {
    return 'transparent'
  }

  if (isValidHexColor(color)) {
    return normalizeHexColor(color)
  }

  const rgba = parseRgbaColor(color)
  if (rgba) {
    return `rgba(${rgba.r}, ${rgba.g}, ${rgba.b}, ${rgba.a})`
  }

  const rgb = parseRgbColor(color)
  if (rgb) {
    return `rgb(${rgb.r}, ${rgb.g}, ${rgb.b})`
  }

  return undefined
}

export function getColorInputValue(value, fallback = '#ffffff') {
  if (!value || typeof value !== 'string') {
    return fallback
  }

  const color = value.trim()

  if (isValidHexColor(color)) {
    return hexToSixDigitHex(color)
  }

  const rgba = parseRgbaColor(color)
  if (rgba) {
    return rgbToHex(rgba.r, rgba.g, rgba.b)
  }

  const rgb = parseRgbColor(color)
  if (rgb) {
    return rgbToHex(rgb.r, rgb.g, rgb.b)
  }

  return fallback
}

export function getBackgroundOpacity(value) {
  if (!value || typeof value !== 'string') {
    return 1
  }

  const color = value.trim()

  if (color === 'transparent') {
    return 0
  }

  if (isValidHexColor(color)) {
    const hex = color.replace('#', '')

    if (hex.length === 4) {
      const alphaHex = hex[3] + hex[3]
      return roundAlpha(parseInt(alphaHex, 16) / 255)
    }

    if (hex.length === 8) {
      const alphaHex = hex.slice(6, 8)
      return roundAlpha(parseInt(alphaHex, 16) / 255)
    }

    return 1
  }

  const rgba = parseRgbaColor(color)
  if (rgba) {
    return rgba.a
  }

  return 1
}

export function buildBackgroundColorValue(hexColor, opacity = 1) {
  const safeHex = isValidHexColor(hexColor) ? hexToSixDigitHex(hexColor) : '#ffffff'
  const safeOpacity = clampAlpha(opacity)

  if (safeOpacity >= 1) {
    return safeHex
  }

  const rgb = hexToRgb(safeHex)

  return `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${safeOpacity})`
}

export function formatColorLabel(value) {
  return normalizeCssColor(value) || 'پیش‌فرض'
}

export function isValidHexColor(value) {
  return typeof value === 'string' &&
    /^#([0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/.test(value.trim())
}

export function normalizeHexColor(value) {
  const hex = value.trim()

  if (!isValidHexColor(hex)) {
    return undefined
  }

  if (hex.length === 4 || hex.length === 5) {
    return '#' + hex
      .slice(1)
      .split('')
      .map((char) => char + char)
      .join('')
  }

  return hex
}

export function hexToSixDigitHex(value) {
  const normalized = normalizeHexColor(value)

  if (!normalized) {
    return '#ffffff'
  }

  // Strip alpha if present because input[type=color] only accepts #rrggbb
  if (normalized.length === 9) {
    return normalized.slice(0, 7)
  }

  return normalized
}

export function hexToRgb(value) {
  const hex = hexToSixDigitHex(value).replace('#', '')

  return {
    r: parseInt(hex.slice(0, 2), 16),
    g: parseInt(hex.slice(2, 4), 16),
    b: parseInt(hex.slice(4, 6), 16),
  }
}

export function rgbToHex(r, g, b) {
  const toHex = (channel) => {
    const value = clampColorChannel(channel)
    return value.toString(16).padStart(2, '0')
  }

  return `#${toHex(r)}${toHex(g)}${toHex(b)}`
}

export function parseRgbColor(value) {
  if (typeof value !== 'string') return null

  const match = value.trim().match(
    /^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/i
  )

  if (!match) return null

  const r = Number(match[1])
  const g = Number(match[2])
  const b = Number(match[3])

  if (!isValidColorChannel(r) || !isValidColorChannel(g) || !isValidColorChannel(b)) {
    return null
  }

  return { r, g, b }
}

export function parseRgbaColor(value) {
  if (typeof value !== 'string') return null

  const match = value.trim().match(
    /^rgba\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(0|1|0?\.\d+)\s*\)$/i
  )

  if (!match) return null

  const r = Number(match[1])
  const g = Number(match[2])
  const b = Number(match[3])
  const a = Number(match[4])

  if (!isValidColorChannel(r) || !isValidColorChannel(g) || !isValidColorChannel(b)) {
    return null
  }

  return {
    r,
    g,
    b,
    a: clampAlpha(a),
  }
}

export function clampColorChannel(value) {
  const number = Number(value)

  if (!Number.isFinite(number)) return 0

  return Math.min(255, Math.max(0, Math.round(number)))
}

export function isValidColorChannel(value) {
  return Number.isInteger(value) && value >= 0 && value <= 255
}

export function clampAlpha(value) {
  const number = Number(value)

  if (!Number.isFinite(number)) return 1

  return Math.min(1, Math.max(0, Number(number.toFixed(2))))
}

export function roundAlpha(value) {
  return clampAlpha(value)
}
