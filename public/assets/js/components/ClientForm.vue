<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3">
        <i class="bi bi-person-plus me-2"></i>
        {{ isEdit ? 'Modifier le client' : 'Nouveau client' }}
      </h1>
      <router-link to="/clients" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Retour
      </router-link>
    </div>

    <div class="card">
      <div class="card-body">
        <form @submit.prevent="saveClient">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="first_name" class="form-label">Prénom *</label>
              <input
                  type="text"
                  class="form-control"
                  id="first_name"
                  v-model="form.first_name"
                  required
                  :class="{ 'is-invalid': errors.first_name }"
              >
              <div class="invalid-feedback" v-if="errors.first_name">{{ errors.first_name }}</div>
            </div>

            <div class="col-md-6">
              <label for="last_name" class="form-label">Nom *</label>
              <input
                  type="text"
                  class="form-control"
                  id="last_name"
                  v-model="form.last_name"
                  required
                  :class="{ 'is-invalid': errors.last_name }"
              >
              <div class="invalid-feedback" v-if="errors.last_name">{{ errors.last_name }}</div>
            </div>

            <div class="col-md-6">
              <label for="email" class="form-label">Email *</label>
              <input
                  type="email"
                  class="form-control"
                  id="email"
                  v-model="form.email"
                  required
                  :class="{ 'is-invalid': errors.email }"
              >
              <div class="invalid-feedback" v-if="errors.email">{{ errors.email }}</div>
            </div>

            <div class="col-md-6">
              <label for="phone" class="form-label">Téléphone</label>
              <input
                  type="tel"
                  class="form-control"
                  id="phone"
                  v-model="form.phone"
                  placeholder="01 23 45 67 89"
              >
            </div>

            <div class="col-md-6">
              <label for="company" class="form-label">Entreprise</label>
              <input
                  type="text"
                  class="form-control"
                  id="company"
                  v-model="form.company"
                  placeholder="Nom de l'entreprise (optionnel)"
              >
            </div>

            <div class="col-md-6">
              <label for="address" class="form-label">Adresse</label>
              <input
                  type="text"
                  class="form-control"
                  id="address"
                  v-model="form.address"
                  placeholder="Adresse (optionnel)"
              >
            </div>

            <div class="col-md-4">
              <label for="city" class="form-label">Ville</label>
              <input
                  type="text"
                  class="form-control"
                  id="city"
                  v-model="form.city"
                  placeholder="Ville"
              >
            </div>

            <div class="col-md-4">
              <label for="postal_code" class="form-label">Code postal</label>
              <input
                  type="text"
                  class="form-control"
                  id="postal_code"
                  v-model="form.postal_code"
                  placeholder="Code postal"
              >
            </div>

            <div class="col-12">
              <button type="submit" class="btn btn-primary" :disabled="saving">
                <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                {{ isEdit ? 'Mettre à jour' : 'Créer' }}
              </button>
              <router-link to="/clients" class="btn btn-secondary ms-2">Annuler</router-link>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useClientStore } from '../stores/client'

const route = useRoute()
const router = useRouter()
const clientStore = useClientStore()

const isEdit = computed(() => !!route.params.id)
const saving = ref(false)
const errors = ref({})

const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  company: '',
  address: '',
  city: '',
  postal_code: ''
})

onMounted(async () => {
  if (isEdit.value) {
    await clientStore.fetchAll()
    const item = clientStore.clients.find(c => c.id === parseInt(route.params.id))
    if (item) {
      form.value = {
        first_name: item.first_name || '',
        last_name: item.last_name || '',
        email: item.email || '',
        phone: item.phone || '',
        company: item.company || '',
        address: item.address || '',
        city: item.city || '',
        postal_code: item.postal_code || ''
      }
    }
  }
})

const saveClient = async () => {
  errors.value = {}
  saving.value = true

  try {
    if (isEdit.value) {
      await clientStore.update(parseInt(route.params.id), form.value)
    } else {
      await clientStore.create(form.value)
    }
    router.push('/clients')
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      errors.value = { general: error.message }
    }
  } finally {
    saving.value = false
  }
}
</script>