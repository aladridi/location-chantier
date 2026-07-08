<template>
  <div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    <div class="bg-dark text-white" id="sidebar-wrapper">
      <div class="sidebar-heading text-center py-4 primary-text fs-4 fw-bold text-uppercase border-bottom">
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
      </div>
    </div>

    <!-- Page Content -->
    <div id="page-content-wrapper" class="w-100">
      <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
        <div class="container-fluid">
          <button class="btn btn-primary" id="menu-toggle">
            <i class="bi bi-list"></i>
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
</template>

<script setup>
import { ref, computed } from 'vue'

const currentDate = computed(() => {
  const now = new Date()
  return now.toLocaleString('fr-FR')
})

// Toggle sidebar sur mobile
const toggleSidebar = () => {
  const sidebar = document.getElementById('sidebar-wrapper')
  sidebar.classList.toggle('toggled')
}
</script>

<style scoped>
#sidebar-wrapper {
  position: fixed;
  left: 0;
  top: 0;
  height: 100vh;
  z-index: 1000;
  width: 250px;
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
}

@media (max-width: 768px) {
  #sidebar-wrapper {
    margin-left: -250px;
    transition: margin-left 0.3s ease;
  }

  #sidebar-wrapper.toggled {
    margin-left: 0;
  }

  #page-content-wrapper {
    margin-left: 0;
  }
}
</style>