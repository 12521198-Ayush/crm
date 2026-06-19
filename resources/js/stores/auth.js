import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('ninja_token') || null,
    booted: false,
  }),
  getters: {
    isMaster: (s) => s.user?.role === 'master',
    isSubMaster: (s) => s.user?.role === 'sub_master',
    isAgent: (s) => s.user?.role === 'agent',
    roleLabel: (s) => ({ master: 'Master', sub_master: 'Sub Master', agent: 'Agent' })[s.user?.role] ?? 'Guest',
  },
  actions: {
    async bootstrap() {
      if (!this.token) { this.booted = true; return; }
      try {
        const { data } = await axios.get('/api/me');
        this.user = data.user;
      } catch (_) { this.token = null; localStorage.removeItem('ninja_token'); }
      this.booted = true;
    },
    async login(email, password) {
      const { data } = await axios.post('/api/login', { email, password });
      this.token = data.token;
      this.user  = data.user;
      localStorage.setItem('ninja_token', data.token);
      axios.defaults.headers.common['Authorization'] = `Bearer ${data.token}`;
    },
    async logout() {
      try { await axios.post('/api/logout'); } catch (_) {}
      this.user = null;
      this.token = null;
      localStorage.removeItem('ninja_token');
      delete axios.defaults.headers.common['Authorization'];
    },
  },
});
