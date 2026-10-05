<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3"><i class="bi bi-people me-2"></i>Clients</h1>
      <router-link to="/clients/create" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Nouveau client
      </router-link>
    </div>

    <div class="card">
      <div class="card-body">
        <div class="row mb-3">
          <div class="col-md-4">
            <input
                type="text"
                class="form-control"
                placeholder="Rechercher un client..."
                v-model="search"
            >
          </div>
        </div>

        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Chargement...</span>
          </div>
        </div>

        <div v-else-if="error" class="alert alert-danger">
          {{ error }}
        </div>

        <div v-else-if="filteredClients.length === 0" class="text-center py-5 text-muted">
          <i class="bi bi-inbox fs-1 d-block mb-3"></i>
          <p>Aucun client trouvé</p>
        </div>

        <table v-else class="table table-striped table-hover">
          <thead>
          <tr>
            <th>Nom</th>
            <th>Email</th>
            <th>Téléphone</th>
            <th>Entreprise</th>
            <th>Actions</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="client in filteredClients" :key="client.id">
            <td>{{ client.full_name || client.first_name + ' ' + client.last_name }}</td>
            <td>{{ client.email }}</td>
            <td>{{ client.phone || '-' }}</td>
            <td>{{ client.company || '-' }}</td>
            <td>
              <div class="btn-group btn-group-sm">
                <router-link :to="'/clients/' + client.id + '/edit'" class="btn btn-outline-primary">
                  <i class="bi bi-pencil"></i>
                </router-link>
                <button class="btn btn-outline-danger" @click="confirmDelete(client)">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </td>
          </tr>
          </tbody>
        </table>
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
            <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>
            <p>Êtes-vous sûr de vouloir supprimer le client <strong>{{ itemToDelete?.full_name || itemToDelete?.first_name + ' ' + itemToDelete?.last_name }}</strong> ?</p>
            <p class="text-danger"><small>Cette action est irréversible.</small></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="button" class="btn btn-danger" @click="deleteClient" :disabled="deleting">
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
import Modal from 'bootstrap/js/dist/modal'
import { useClientStore } from '../stores/client'

const clientStore = useClientStore()
const clients = computed(() => clientStore.clients)
const loading = computed(() => clientStore.loading)
const error = computed(() => clientStore.error)

const search = ref('')
const filteredClients = computed(() => {
  const term = search.value.trim().toLowerCase()
  if (!term) return clients.value
  return clients.value.filter(client =>
    [client.full_name, client.first_name, client.last_name, client.email, client.company]
      .some(value => value?.toLowerCase().includes(term))
  )
})
const itemToDelete = ref(null)
const deleting = ref(false)
const deleteModalRef = ref(null)

onMounted(async () => {
  await loadClients()
})

const loadClients = async () => {
  try {
    await clientStore.fetchAll()
  } catch {
    // Le store affiche déjà le message de l'API.
  }
}

const confirmDelete = (client) => {
  clientStore.error = null
  itemToDelete.value = client
  const modal = Modal.getOrCreateInstance(deleteModalRef.value)
  modal.show()
}

const deleteClient = async () => {
  if (!itemToDelete.value) return

  deleting.value = true
  try {
    await clientStore.delete(itemToDelete.value.id)
    const modal = Modal.getInstance(deleteModalRef.value)
    modal.hide()
    itemToDelete.value = null
  } catch (error) {
    console.error('Erreur lors de la suppression:', error)
  } finally {
    deleting.value = false
  }
}
</script>