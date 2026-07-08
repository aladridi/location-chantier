<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3"><i class="bi bi-tools me-2"></i>Équipements</h1>
      <router-link to="/equipment/create" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Nouvel équipement
      </router-link>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <input type="text" class="form-control" placeholder="Rechercher..." v-model="filters.search">
          </div>
          <div class="col-md-2">
            <select class="form-select" v-model="filters.category">
              <option value="">Toutes catégories</option>
              <option v-for="cat in categories" :key="cat" :value="cat">{{ formatCategory(cat) }}</option>
            </select>
          </div>
          <div class="col-md-2">
            <select class="form-select" v-model="filters.available">
              <option value="">Tous</option>
              <option value="1">Disponible</option>
              <option value="0">Loué</option>
            </select>
          </div>
          <div class="col-md-2">
            <button class="btn btn-outline-secondary w-100" @click="applyFilters">
              <i class="bi bi-search me-2"></i>Filtrer
            </button>
          </div>
          <div class="col-md-2">
            <button class="btn btn-outline-danger w-100" @click="resetFilters">
              <i class="bi bi-arrow-counterclockwise me-2"></i>Réinitialiser
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Liste des équipements -->
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

        <div v-else-if="paginatedEquipment.length === 0" class="text-center py-5 text-muted">
          <i class="bi bi-inbox fs-1 d-block mb-3"></i>
          <p>Aucun équipement trouvé</p>
        </div>

        <table v-else class="table table-striped table-hover mb-0">
          <thead>
          <tr>
            <th>Nom</th>
            <th>Catégorie</th>
            <th>Prix/jour</th>
            <th>Statut</th>
            <th>Maintenance</th>
            <th>Actions</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="item in paginatedEquipment" :key="item.id">
            <td>{{ item.name }}</td>
            <td>
              <span class="badge bg-info">{{ formatCategory(item.category) }}</span>
            </td>
            <td>{{ formatPrice(item.effective_daily_rate || item.daily_rate) }}</td>
            <td>
                                <span class="badge" :class="item.available ? 'bg-success' : 'bg-warning'">
                                    {{ item.available ? 'Disponible' : 'Loué' }}
                                </span>
            </td>
            <td>
                                <span v-if="item.needs_maintenance" class="badge bg-danger">
                                    <i class="bi bi-exclamation-triangle me-1"></i>Maintenance
                                </span>
              <span v-else class="badge bg-success">
                                    <i class="bi bi-check-circle me-1"></i>OK
                                </span>
            </td>
            <td>
              <div class="btn-group btn-group-sm">
                <router-link :to="'/equipment/' + item.id + '/edit'" class="btn btn-outline-primary">
                  <i class="bi bi-pencil"></i>
                </router-link>
                <button class="btn btn-outline-danger" @click="confirmDelete(item)">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </td>
          </tr>
          </tbody>
        </table>
      </div>

      <div class="card-footer bg-white" v-if="equipment.length > 0">
        <nav>
          <ul class="pagination justify-content-center mb-0">
            <li class="page-item" :class="{ disabled: currentPage === 1 }">
              <a class="page-link" href="#" @click.prevent="changePage(currentPage - 1)">Précédent</a>
            </li>
            <li class="page-item" v-for="page in totalPages" :key="page" :class="{ active: page === currentPage }">
              <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
            </li>
            <li class="page-item" :class="{ disabled: currentPage === totalPages }">
              <a class="page-link" href="#" @click.prevent="changePage(currentPage + 1)">Suivant</a>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <!-- Modal de confirmation -->
    <div class="modal fade" id="deleteModal" tabindex="-1" ref="deleteModalRef">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirmer la suppression</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p>Êtes-vous sûr de vouloir supprimer l'équipement <strong>{{ itemToDelete?.name }}</strong> ?</p>
            <p class="text-danger"><small>Cette action est irréversible.</small></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="button" class="btn btn-danger" @click="deleteEquipment" :disabled="deleting">
              <span v-if="deleting" class="spinner-border spinner-border-sm me-2"></span>
              Supprimer
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useEquipmentStore } from '../stores/equipment'

const equipmentStore = useEquipmentStore()
const equipment = computed(() => equipmentStore.equipment)
const loading = computed(() => equipmentStore.loading)
const error = computed(() => equipmentStore.error)

const filters = ref({
  search: '',
  category: '',
  available: ''
})

const categories = [
  'bulldozer', 'crane', 'excavator', 'loader',
  'dump_truck', 'compressor', 'generator', 'scaffolding',
  'concrete_mixer', 'other'
]

const currentPage = ref(1)
const totalPages = ref(1)
const itemsPerPage = 10

const itemToDelete = ref(null)
const deleting = ref(false)
const deleteModalRef = ref(null)

const formatCategory = (cat) => {
  const labels = {
    bulldozer: 'Bulldozer',
    crane: 'Grue',
    excavator: 'Excavatrice',
    loader: 'Chargeuse',
    dump_truck: 'Camion-benne',
    compressor: 'Compresseur',
    generator: 'Générateur',
    scaffolding: 'Échafaudage',
    concrete_mixer: 'Bétonnière',
    other: 'Autre'
  }
  return labels[cat] || cat
}

onMounted(async () => {
  await loadEquipment()
})

const loadEquipment = async () => {
  await equipmentStore.fetchAll()
  totalPages.value = Math.ceil(equipment.value.length / itemsPerPage)
}

const applyFilters = async () => {
  await equipmentStore.fetchAll()
  let filtered = [...equipmentStore.equipment]

  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    filtered = filtered.filter(item =>
        item.name.toLowerCase().includes(search) ||
        item.category.includes(search)
    )
  }

  if (filters.value.category) {
    filtered = filtered.filter(item => item.category === filters.value.category)
  }

  if (filters.value.available !== '') {
    filtered = filtered.filter(item => item.available === (filters.value.available === '1'))
  }

  equipmentStore.equipment = filtered
  currentPage.value = 1
  totalPages.value = Math.ceil(filtered.length / itemsPerPage)
}

const resetFilters = () => {
  filters.value = {
    search: '',
    category: '',
    available: ''
  }
  applyFilters()
}

const changePage = (page) => {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(price)
}

const confirmDelete = (item) => {
  itemToDelete.value = item
  const modal = new window.bootstrap.Modal(deleteModalRef.value)
  modal.show()
}

const deleteEquipment = async () => {
  if (!itemToDelete.value) return

  deleting.value = true
  try {
    await equipmentStore.delete(itemToDelete.value.id)
    const modal = window.bootstrap.Modal.getInstance(deleteModalRef.value)
    modal.hide()
    await loadEquipment()
  } catch (error) {
    console.error('Erreur lors de la suppression:', error)
  } finally {
    deleting.value = false
    itemToDelete.value = null
  }
}

const paginatedEquipment = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return equipment.value.slice(start, end)
})
</script>

<style scoped>
.bi {
  line-height: 1;
}
</style>