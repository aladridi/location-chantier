import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'

// Composants
import App from './App.vue'
import Login from './components/Login.vue'
import Register from './components/Register.vue'
import Dashboard from './components/Dashboard.vue'
import EquipmentList from './components/EquipmentList.vue'
import EquipmentForm from './components/EquipmentForm.vue'
import ClientList from './components/ClientList.vue'
import ClientForm from './components/ClientForm.vue'
import RentalList from './components/RentalList.vue'
import RentalForm from './components/RentalForm.vue'

// Stores
import { useAuthStore } from './stores/auth'

// Routes
const router = createRouter({
    history: createWebHistory(),
    routes: [
        // Routes publiques
        { path: '/login', component: Login, name: 'login', meta: { public: true } },
        { path: '/register', component: Register, name: 'register', meta: { public: true } },

        // Routes protégées
        { path: '/', component: Dashboard, name: 'dashboard', meta: { requiresAuth: true } },
        { path: '/equipment', component: EquipmentList, name: 'equipment', meta: { requiresAuth: true } },
        { path: '/equipment/create', component: EquipmentForm, name: 'equipment.create', meta: { requiresAuth: true } },
        { path: '/equipment/:id/edit', component: EquipmentForm, name: 'equipment.edit', meta: { requiresAuth: true } },
        { path: '/clients', component: ClientList, name: 'clients', meta: { requiresAuth: true } },
        { path: '/clients/create', component: ClientForm, name: 'client.create', meta: { requiresAuth: true } },
        { path: '/clients/:id/edit', component: ClientForm, name: 'client.edit', meta: { requiresAuth: true } },
        { path: '/rentals', component: RentalList, name: 'rentals', meta: { requiresAuth: true } },
        { path: '/rentals/create', component: RentalForm, name: 'rental.create', meta: { requiresAuth: true } },
        { path: '/rentals/:id', component: RentalForm, name: 'rental.show', meta: { requiresAuth: true } }
    ]
})

// ✅ Navigation guard pour l'authentification
router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore()

    // Vérifier l'authentification
    await authStore.checkAuth()

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        // Rediriger vers login avec le paramètre redirect
        next({ name: 'login', query: { redirect: to.fullPath } })
    } else if ((to.name === 'login' || to.name === 'register') && authStore.isAuthenticated) {
        // Si déjà connecté, rediriger vers le dashboard
        next({ name: 'dashboard' })
    } else {
        next()
    }
})

// Pinia
const pinia = createPinia()

// App
const app = createApp(App)
app.use(pinia)
app.use(router)
app.mount('#app')