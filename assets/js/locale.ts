const PLACEHOLDER_PATTERN = /\{(\w+)\}/;

/**
 * Stub function until a proper localization library is chosen.
 */
export function t(message: string, data?: Record<string, string | number>): string {
  return message.replace(PLACEHOLDER_PATTERN, function (_match, key) {
    if (data?.hasOwnProperty(key)) {
      return String(data[key]);
    }
    return '';
  });
}
