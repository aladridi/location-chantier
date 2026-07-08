<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3">
        <i class="bi bi-tools me-2"></i>
        {{ isEdit ? 'Modifier l\'équipement' : 'Nouvel équipement' }}
      </h1>
      <router-link to="/equipment" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Retour
      </router-link>
    </div>

    <div class="card">
      <div class="card-body">
        <form @submit.prevent="saveEquipment">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="name" class="form-label">Nom *</label>
              <input
                  type="text"
                  class="form-control"
                  id="name"
                  v-model="form.name"
                  required
                  :class="{ 'is-invalid': errors.name }"
              >
              <div class="invalid-feedback" v-if="errors.name">{{ errors.name }}</div>
            </div>

            <div class="col-md-6">
              <label for="category" class="form-label">Catégorie *</label>
              <select
                  class="form-select"
                  id="category"
                  v-model="form.category"
                  required
                  :class="{ 'is-invalid': errors.category }"
              >
                <option value="">Sélectionner une catégorie</option>
                <option v-for="cat in categories" :key="cat" :value="cat">
                  {{ formatCategory(cat) }}
                </option>
              </select>
              <div class="invalid-feedback" v-if="errors.category">{{ errors.category }}</div>
            </div>

            <div class="col-md-4">
              <label for="daily_rate" class="form-label">Prix journalier (€) *</label>
              <input
                  type="number"
                  class="form-control"
                  id="daily_rate"
                  v-model.number="form.daily_rate"
                  step="0.01"
                  min="0"
                  required
                  :class="{ 'is-invalid': errors.daily_rate }"
              >
              <div class="invalid-feedback" v-if="errors.daily_rate">{{ errors.daily_rate }}</div>
            </div>

            <div class="col-md-4">
              <label for="available" class="form-label">Disponibilité</label>
              <select class="form-select" id="available" v-model="form.available">
                <option :value="true">Disponible</option>
                <option :value="false">Loué</option>
              </select>
            </div>

            <div class="col-md-4">
              <label for="serial_number" class="form-label">Numéro de série</label>
              <input
                  type="text"
                  class="form-control"
                  id="serial_number"
                  v-model="form.serial_number"
                  placeholder="Optionnel"
              >
            </div>

            <div class="col-12">
              <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Le prix effectif sera calculé automatiquement selon la catégorie.
              </div>
            </div>

            <div class="col-12">
              <button type="submit" class="btn btn-primary" :disabled="saving">
                <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                {{ isEdit ? 'Mettre à jour' : 'Créer' }}
              </button>
              <router-link to="/equipment" class="btn btn-secondary ms-2">Annuler</router-link>
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
import { useEquipmentStore } from '../stores/equipment'

const route = useRoute()
const router = useRouter()
const equipmentStore = useEquipmentStore()

const isEdit = computed(() => !!route.params.id)
const saving = ref(false)
const errors = ref({})

const categories = [
  'bulldozer', 'crane', 'excavator', 'loader',
  'dump_truck', 'compressor', 'generator', 'scaffolding',
  'concrete_mixer', 'other'
]

const form = ref({
  name: '',
  category: '',
  daily_rate: 0,
  available: true,
  serial_number: ''
})

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
  if (isEdit.value) {
    await equipmentStore.fetchAll()
    const item = equipmentStore.equipment.find(e => e.id === parseInt(route.params.id))
    if (item) {
      form.value = {
        name: item.name,
        category: item.category,
        daily_rate: item.daily_rate,
        available: item.available,
        serial_number: item.serial_number || ''
      }
    }
  }
})

const saveEquipment = async () => {
  errors.value = {}
  saving.value = true

  try {
    if (isEdit.value) {
      await equipmentStore.update(parseInt(route.params.id), form.value)
    } else {
      await equipmentStore.create(form.value)
    }
    router.push('/equipment')
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