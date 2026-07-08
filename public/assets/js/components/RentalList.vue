<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3"><i class="bi bi-clock-history me-2"></i>Locations</h1>
      <router-link to="/rentals/create" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Nouvelle location
      </router-link>
    </div>

    <div class="card">
      <div class="card-body p-0">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Chargement...</span>
          </div>
        </div>

        <div v-else-if="error" class="alert alert-danger m-3">
          {{ error }}
        </div>

        <div v-else-if="rentals.length === 0" class="text-center py-5 text-muted">
          <i class="bi bi-inbox fs-1 d-block mb-3"></i>
          <p>Aucune location trouvée</p>
        </div>

        <table v-else class="table table-striped table-hover mb-0">
          <thead>
          <tr>
            <th>ID</th>
            <th>Client</th>
            <th>Équipement</th>
            <th>Période</th>
            <th>Prix</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="rental in rentals" :key="rental.id">
            <td>#{{ rental.id }}</td>
            <td>{{ rental.client_name }}</td>
            <td>{{ rental.equipment_name }}</td>
            <td>
              <small>
                {{ formatDate(rental.start_date) }}<br>
                <span class="text-muted">→</span> {{ formatDate(rental.end_date) }}
              </small>
            </td>
            <td>{{ formatPrice(rental.total_price) }}</td>
            <td>
                                <span class="badge" :class="getStatusBadge(rental.status)">
                                    {{ rental.status_label }}
                                </span>
            </td>
            <td>
              <div class="btn-group btn-group-sm">
                <router-link :to="'/rentals/' + rental.id" class="btn btn-outline-primary">
                  <i class="bi bi-eye"></i>
                </router-link>
                <button v-if="rental.status === 'active'"
                        class="btn btn-outline-success"
                        @click="returnRental(rental)">
                  <i class="bi bi-arrow-return-left"></i>
                </button>
              </div>
            </td>
          </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRentalStore } from '../stores/rental'

const rentalStore = useRentalStore()
const rentals = computed(() => rentalStore.rentals)
const loading = computed(() => rentalStore.loading)
const error = computed(() => rentalStore.error)

onMounted(async () => {
  await rentalStore.fetchAll()
})

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR')
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(price)
}

const getStatusBadge = (status) => {
  const badges = {
    pending: 'bg-warning',
    active: 'bg-primary',
    overdue: 'bg-danger',
    returned: 'bg-success',
    damaged: 'bg-dark'
  }
  return badges[status] || 'bg-secondary'
}

const returnRental = async (rental) => {
  if (!confirm(`Retourner la location #${rental.id} ?`)) return

  try {
    await rentalStore.returnRental(rental.id)
    await rentalStore.fetchAll()
  } catch (error) {
    console.error('Erreur lors du retour:', error)
    alert('Erreur lors du retour de la location')
  }
}
</script>