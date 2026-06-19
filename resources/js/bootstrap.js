import axios from 'axios';

window.axios = axios;
axios.defaults.baseURL = '/';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

// Attach token if present
const token = localStorage.getItem('ninja_token');
if (token) axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;

axios.interceptors.response.use(
  (r) => r,
  (err) => {
    if (err?.response?.status === 401) {
      localStorage.removeItem('ninja_token');
      delete axios.defaults.headers.common['Authorization'];
      if (!window.location.pathname.startsWith('/auth')) window.location.href = '/auth/login';
    }
    return Promise.reject(err);
  }
);
