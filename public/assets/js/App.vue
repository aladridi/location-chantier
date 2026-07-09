<template>
  <div v-if="authStore.loading" class="d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="text-center">
      <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
        <span class="visually-hidden">Chargement...</span>
      </div>
      <p class="mt-3 text-muted">Chargement...</p>
    </div>
  </div>

  <div v-else-if="!authStore.isAuthenticated">
    <router-view></router-view>
  </div>

  <div v-else>
    <div class="d-flex" id="wrapper">
      <!-- Sidebar -->
      <div class="bg-dark text-white" id="sidebar-wrapper">
        <div class="sidebar-heading text-center py-4 fs-4 fw-bold text-uppercase border-bottom">
          <i class="bi bi-tools me-2"></i>Location
        </div>
        <div class="list-group list-group-flush my-3">
          <router-link
              to="/"
              class="list-group-item list-group-item-action bg-transparent text-white"
              active-class="active"
          >
            <i class="bi bi-speedometer2 me-2"></i>Dashboard
          </router-link>
          <router-link
              to="/equipment"
              class="list-group-item list-group-item-action bg-transparent text-white"
              active-class="active"
          >
            <i class="bi bi-tools me-2"></i>Équipements
          </router-link>
          <router-link
              to="/clients"
              class="list-group-item list-group-item-action bg-transparent text-white"
              active-class="active"
          >
            <i class="bi bi-people me-2"></i>Clients
          </router-link>
          <router-link
              to="/rentals"
              class="list-group-item list-group-item-action bg-transparent text-white"
              active-class="active"
          >
            <i class="bi bi-clock-history me-2"></i>Locations
          </router-link>
          <router-link
              to="/rentals/create"
              class="list-group-item list-group-item-action bg-transparent text-white"
              active-class="active"
          >
            <i class="bi bi-plus-circle me-2"></i>Nouvelle Location
          </router-link>
          <a href="#" class="list-group-item list-group-item-action bg-transparent text-warning"
             @click.prevent="handleLogout">
            <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
          </a>
        </div>

        <div class="position-absolute bottom-0 w-100 p-3 border-top">
          <div class="d-flex align-items-center">
            <div class="bg-success rounded-circle p-1 me-2"></div>
            <div class="small">
              <div class="fw-bold">{{ authStore.username }}</div>
              <div class="text-white-50">{{ authStore.email }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Page Content -->
      <div id="page-content-wrapper" class="w-100">
        <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
          <div class="container-fluid">
            <button class="btn" id="menu-toggle" @click="toggleSidebar">
              <i class="bi bi-list fs-4"></i>
            </button>
            <span class="navbar-text ms-3">
                            <i class="bi bi-calendar3 me-1"></i> {{ currentDate }}
                        </span>
            <div class="ms-auto">
              <span class="badge bg-success">En ligne</span>
            </div>
          </div>
        </nav>
        <div class="container-fluid px-4 py-4">
          <router-view></router-view>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const currentDate = computed(() => {
  return new Date().toLocaleString('fr-FR')
})

const toggleSidebar = () => {
  const sidebar = document.getElementById('sidebar-wrapper')
  if (sidebar) {
    sidebar.classList.toggle('toggled')
  }
}

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}

onMounted(async () => {
  await authStore.checkAuth()
})
</script>

<style scoped>
#sidebar-wrapper {
  position: fixed;
  left: 0;
  top: 0;
  height: 100vh;
  z-index: 1000;
  width: 250px;
  transition: margin-left 0.3s ease;
}

#sidebar-wrapper .list-group-item {
  border: none;
  padding: 12px 24px;
}

#sidebar-wrapper .list-group-item:hover {
  background-color: rgba(255,255,255,0.1) !important;
}

#sidebar-wrapper .list-group-item.active {
  background-color: #0d6efd !important;
  color: white !important;
}

#page-content-wrapper {
  margin-left: 250px;
  min-height: 100vh;
}

#menu-toggle {
  background-color: transparent;
  border: none;
  color: #0d6efd;
  font-size: 1.5rem;
}

@media (max-width: 768px) {
  #sidebar-wrapper {
    margin-left: -250px;
  }

  #sidebar-wrapper.toggled {
    margin-left: 0;
  }

  #page-content-wrapper {
    margin-left: 0;
  }
}
</style>