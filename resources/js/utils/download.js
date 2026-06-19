import axios from 'axios';

/**
 * Download a file from an authenticated API endpoint.
 *
 * Browser navigations (`window.location.href`, `<a href>`, `window.open`) do
 * NOT send the `Authorization: Bearer` header, so hitting an auth-protected
 * route that way is unauthenticated → 401/500. This fetches the file via axios
 * (which carries the token) as a blob and saves it client-side.
 *
 * @param {string} url      API path, e.g. '/api/leads/import-template'
 * @param {object} params   query params (format, filters, …)
 * @param {string} fallback fallback filename if server sends none
 */
export async function downloadFile(url, params = {}, fallback = 'download') {
  const res = await axios.get(url, { params, responseType: 'blob' });

  // Prefer the server-provided filename from Content-Disposition.
  let filename = fallback;
  const cd = res.headers['content-disposition'] || res.headers['Content-Disposition'];
  if (cd) {
    const m = /filename\*?=(?:UTF-8'')?"?([^";]+)"?/i.exec(cd);
    if (m && m[1]) filename = decodeURIComponent(m[1]);
  }

  const blob = new Blob([res.data], { type: res.data.type || 'application/octet-stream' });
  const objectUrl = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = objectUrl;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  a.remove();
  window.URL.revokeObjectURL(objectUrl);
}
