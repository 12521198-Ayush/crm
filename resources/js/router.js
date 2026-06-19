import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './stores/auth';

import AuthLayout from './layouts/AuthLayout.vue';
import AppLayout from './layouts/AppLayout.vue';

import Login from './pages/Login.vue';
import Dashboard from './pages/Dashboard.vue';
import Leads from './pages/Leads.vue';
import LeadDetail from './pages/LeadDetail.vue';
import Projects from './pages/Projects.vue';
import Statuses from './pages/Statuses.vue';
import Sources from './pages/Sources.vue';
import Users from './pages/Users.vue';
import Profile from './pages/Profile.vue';
import NotFound from './pages/NotFound.vue';

const routes = [
  {
    path: '/auth',
    component: AuthLayout,
    children: [
      { path: '', redirect: { name: 'login' } },
      { path: 'login', name: 'login', component: Login, meta: { guestOnly: true } },
    ],
  },
  {
    path: '/',
    component: AppLayout,
    meta: { auth: true },
    children: [
      { path: '', redirect: { name: 'dashboard' } },
      { path: 'dashboard', name: 'dashboard', component: Dashboard },
      { path: 'leads', name: 'leads', component: Leads },
      { path: 'leads/:id', name: 'lead-detail', component: LeadDetail, props: true },
      { path: 'projects', name: 'projects', component: Projects, meta: { roles: ['master', 'sub_master'] } },
      { path: 'statuses', name: 'statuses', component: Statuses, meta: { roles: ['master', 'sub_master'] } },
      { path: 'sources', name: 'sources', component: Sources, meta: { roles: ['master'] } },
      { path: 'users', name: 'users', component: Users, meta: { roles: ['master', 'sub_master'] } },
      { path: 'profile', name: 'profile', component: Profile },
    ],
  },
  { path: '/:pathMatch(.*)*', component: NotFound },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() { return { top: 0 }; },
});

router.beforeEach(async (to) => {
  const auth = useAuthStore();
  if (to.meta.auth && !auth.user) return { name: 'login', query: { redirect: to.fullPath } };
  if (to.meta.guestOnly && auth.user) return { name: 'dashboard' };
  if (to.meta.roles && auth.user && !to.meta.roles.includes(auth.user.role)) return { name: 'dashboard' };
  return true;
});

export default router;
