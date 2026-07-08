import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'

// Composants
import App from './App.vue'
import Dashboard from './components/Dashboard.vue'
import EquipmentList from './components/EquipmentList.vue'
import EquipmentForm from './components/EquipmentForm.vue'
import ClientList from './components/ClientList.vue'
import ClientForm from './components/ClientForm.vue'
import RentalList from './components/RentalList.vue'
import RentalForm from './components/RentalForm.vue'
import HelloWorld from './components/HelloWorld.vue'

// Stores
import { useEquipmentStore } from './stores/equipment'
import { useClientStore } from './stores/client'
import { useRentalStore } from './stores/rental'

// Routes
const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', component: Dashboard, name: 'dashboard' },
        { path: '/dashboard', component: Dashboard, name: 'dashboard' },
        { path: '/equipment', component: EquipmentList, name: 'equipment' },
        { path: '/equipment/create', component: EquipmentForm, name: 'equipment.create' },
        { path: '/equipment/:id/edit', component: EquipmentForm, name: 'equipment.edit' },
        { path: '/clients', component: ClientList, name: 'clients' },
        { path: '/clients/create', component: ClientForm, name: 'client.create' },
        { path: '/clients/:id/edit', component: ClientForm, name: 'client.edit' },
        { path: '/rentals', component: RentalList, name: 'rentals' },
        { path: '/rentals/create', component: RentalForm, name: 'rental.create' },
        { path: '/rentals/:id', component: RentalForm, name: 'rental.show' }
    ]
})

// Pinia
const pinia = createPinia()

// App
const app = createApp(App)
app.use(pinia)
app.use(router)
app.mount('#app')