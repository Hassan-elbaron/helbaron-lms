/**
 * Replace `{token}` placeholders in a string.
 *
 * Used to keep the academy's name OUT of the dictionaries and config objects. Those are build-time
 * constants: a value baked into them cannot vary per instance, and this product ships one instance
 * per customer. A `{brand}` token resolved at render lets the same bundle serve any academy, with
 * the real value arriving from the branding API.
 *
 * An unknown token is left EXACTLY as written rather than blanked — a visible `{whoops}` in the UI
 * is a bug someone reports, whereas a silently empty sentence is a bug nobody notices.
 */
export function interpolate(text: string, vars: Record<string, string | number | undefined>): string {
  if (!text.includes("{")) return text;

  return text.replace(/\{(\w+)\}/g, (match, token: string) => {
    const value = vars[token];
    return value === undefined || value === "" ? match : String(value);
  });
}
