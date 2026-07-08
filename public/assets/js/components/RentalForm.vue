<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3">
        <i class="bi bi-calendar-plus me-2"></i>
        {{ isEdit ? 'Détails de la location' : 'Nouvelle location' }}
      </h1>
      <router-link to="/rentals" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Retour
      </router-link>
    </div>

    <div class="card">
      <div class="card-body">
        <form @submit.prevent="saveRental" v-if="!isEdit">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="client_id" class="form-label">Client *</label>
              <select class="form-select" id="client_id" v-model="form.client_id" required>
                <option value="">Sélectionner un client</option>
                <option v-for="client in clients" :key="client.id" :value="client.id">
                  {{ client.full_name || client.first_name + ' ' + client.last_name }}
                </option>
              </select>
            </div>

            <div class="col-md-6">
              <label for="equipment_id" class="form-label">Équipement *</label>
              <select class="form-select" id="equipment_id" v-model="form.equipment_id" required>
                <option value="">Sélectionner un équipement</option>
                <option v-for="item in availableEquipment" :key="item.id" :value="item.id">
                  {{ item.name }} - {{ formatPrice(item.daily_rate) }}/jour
                </option>
              </select>
            </div>

            <div class="col-md-3">
              <label for="days" class="form-label">Nombre de jours *</label>
              <input
                  type="number"
                  class="form-control"
                  id="days"
                  v-model.number="form.days"
                  min="1"
                  required
                  @input="estimatePrice"
              >
            </div>

            <div class="col-md-3">
              <label for="strategy" class="form-label">Stratégie de tarification</label>
              <select class="form-select" id="strategy" v-model="form.strategy" @change="estimatePrice">
                <option value="">Automatique</option>
                <option value="daily">Journalier</option>
                <option value="weekly">Hebdomadaire</option>
                <option value="monthly">Mensuel</option>
              </select>
            </div>

            <div class="col-md-6">
              <div class="card bg-light">
                <div class="card-body">
                  <h6 class="card-title">Estimation du prix</h6>
                  <div v-if="estimating" class="text-center">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                      <span class="visually-hidden">Chargement...</span>
                    </div>
                  </div>
                  <div v-else-if="estimation">
                    <p class="mb-1">
                      Prix estimé :
                      <strong>{{ formatPrice(estimation.best_price) }}</strong>
                    </p>
                    <small class="text-muted">
                      Stratégie : {{ estimation.breakdown?.strategy || 'Automatique' }}
                    </small>
                  </div>
                  <div v-else class="text-muted">
                    <small>Sélectionnez un équipement et une durée</small>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12">
              <button type="submit" class="btn btn-primary" :disabled="saving || !form.client_id || !form.equipment_id || !form.days">
                <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                Créer la location
              </button>
              <router-link to="/rentals" class="btn btn-secondary ms-2">Annuler</router-link>
            </div>
          </div>
        </form>

        <div v-else>
          <div class="row">
            <div class="col-md-6">
              <dl class="row">
                <dt class="col-sm-4">ID</dt>
                <dd class="col-sm-8">#{{ rental.id }}</dd>

                <dt class="col-sm-4">Client</dt>
                <dd class="col-sm-8">{{ rental.client_name }}</dd>

                <dt class="col-sm-4">Équipement</dt>
                <dd class="col-sm-8">{{ rental.equipment_name }}</dd>

                <dt class="col-sm-4">Période</dt>
                <dd class="col-sm-8">
                  {{ formatDate(rental.start_date) }} → {{ formatDate(rental.end_date) }}
                  <br>
                  <small class="text-muted">{{ rental.duration_in_days }} jours</small>
                </dd>
              </dl>
            </div>
            <div class="col-md-6">
              <dl class="row">
                <dt class="col-sm-4">Prix total</dt>
                <dd class="col-sm-8"><strong>{{ formatPrice(rental.total_price) }}</strong></dd>

                <dt class="col-sm-4">Pénalités</dt>
                <dd class="col-sm-8">{{ formatPrice(rental.penalty_amount || 0) }}</dd>

                <dt class="col-sm-4">Statut</dt>
                <dd class="col-sm-8">
                                    <span class="badge" :class="getStatusBadge(rental.status)">
                                        {{ rental.status_label }}
                                    </span>
                </dd>

                <dt class="col-sm-4">Actions</dt>
                <dd class="col-sm-8">
                  <button v-if="rental.status === 'active'"
                          class="btn btn-sm btn-success"
                          @click="returnRental">
                    <i class="bi bi-arrow-return-left me-2"></i>Retourner
                  </button>
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useRentalStore } from '../stores/rental'
import { useEquipmentStore } from '../stores/equipment'
import { useClientStore } from '../stores/client'

const route = useRoute()
const router = useRouter()
const rentalStore = useRentalStore()
const equipmentStore = useEquipmentStore()
const clientStore = useClientStore()

const isEdit = computed(() => !!route.params.id)
const saving = ref(false)
const estimating = ref(false)
const estimation = ref(null)
const rental = ref({})
const clients = ref([])
const availableEquipment = ref([])

const form = ref({
  client_id: '',
  equipment_id: '',
  days: 1,
  strategy: ''
})

onMounted(async () => {
  await Promise.all([
    clientStore.fetchAll(),
    equipmentStore.fetchAll()
  ])

  clients.value = clientStore.clients

  // Filtrer les équipements disponibles
  availableEquipment.value = equipmentStore.equipment.filter(e => e.available)

  if (isEdit.value) {
    await rentalStore.fetchAll()
    const found = rentalStore.rentals.find(r => r.id === parseInt(route.params.id))
    if (found) {
      rental.value = found
    }
  }
})

const estimatePrice = async () => {
  if (!form.value.equipment_id || !form.value.days) {
    estimation.value = null
    return
  }

  estimating.value = true
  try {
    const response = await fetch(`/api/rentals/estimate?equipment_id=${form.value.equipment_id}&days=${form.value.days}&strategy=${form.value.strategy}`)
    const data = await response.json()
    estimation.value = data.data
  } catch (error) {
    console.error('Erreur lors de l\'estimation:', error)
  } finally {
    estimating.value = false
  }
}

const saveRental = async () => {
  saving.value = true
  try {
    await rentalStore.create(form.value)
    router.push('/rentals')
  } catch (error) {
    console.error('Erreur lors de la création:', error)
    alert('Erreur lors de la création de la location')
  } finally {
    saving.value = false
  }
}

const returnRental = async () => {
  if (!confirm(`Retourner la location #${rental.value.id} ?`)) return

  try {
    await rentalStore.returnRental(rental.value.id)
    await rentalStore.fetchAll()
    const found = rentalStore.rentals.find(r => r.id === parseInt(route.params.id))
    if (found) {
      rental.value = found
    }
  } catch (error) {
    console.error('Erreur lors du retour:', error)
    alert('Erreur lors du retour de la location')
  }
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR')
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(price || 0)
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
</script>