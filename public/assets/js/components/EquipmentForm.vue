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

    <div class="row">
      <!-- Formulaire principal -->
      <div class="col-md-8">
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
                    <option
                        v-for="cat in categories"
                        :key="cat.id"
                        :value="cat.slug"
                    >
                      {{ cat.name }}
                      <span v-if="cat.daily_rate_multiplier && cat.daily_rate_multiplier !== 1.0">
                        (x{{ cat.daily_rate_multiplier }})
                      </span>
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
                    <span v-if="selectedCategory && selectedCategory.daily_rate_multiplier && selectedCategory.daily_rate_multiplier !== 1.0">
                      (Multiplicateur: x{{ selectedCategory.daily_rate_multiplier }})
                    </span>
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

      <!-- Section Images (uniquement en édition) -->
      <div class="col-md-4" v-if="isEdit">
        <div class="card">
          <div class="card-header bg-white">
            <h5 class="card-title mb-0">
              <i class="bi bi-images me-2"></i>Images
              <span class="badge bg-secondary ms-2">{{ images.length }}</span>
            </h5>
          </div>
          <div class="card-body">
            <!-- Upload simple -->
            <div class="mb-3">
              <label class="form-label">Ajouter une image</label>
              <div class="drop-zone"
                   @dragover.prevent="dragover = true"
                   @dragleave.prevent="dragover = false"
                   @drop.prevent="handleDrop"
                   :class="{ 'drop-zone-active': dragover }">
                <i class="bi bi-cloud-upload fs-2 d-block mb-2"></i>
                <p class="mb-1">Glissez-déposez une image ici</p>
                <small class="text-muted">ou</small>
                <!-- ✅ Correction : utiliser une ref avec un nom unique -->
                <input type="file"
                       ref="singleImageInput"
                       accept="image/*"
                       @change="uploadSingleImage"
                       class="d-none">
                <button class="btn btn-outline-primary btn-sm mt-2"
                        @click="triggerSingleUpload">
                  <i class="bi bi-upload me-1"></i>Choisir un fichier
                </button>
              </div>
            </div>

            <!-- Upload multiple -->
            <div class="mb-3">
              <label class="form-label">Ajouter plusieurs images</label>
              <!-- ✅ Correction : utiliser une ref avec un nom unique -->
              <input type="file"
                     ref="multipleImageInput"
                     accept="image/*"
                     multiple
                     @change="uploadMultipleImages"
                     class="d-none">
              <button class="btn btn-outline-success w-100"
                      @click="triggerMultipleUpload">
                <i class="bi bi-images me-1"></i>Sélectionner plusieurs images
              </button>
            </div>

            <!-- Liste des images -->
            <div v-if="images.length > 0" class="mt-3">
              <h6 class="fw-bold">Images uploadées</h6>
              <div class="row g-2">
                <div v-for="(image, index) in images" :key="image.id || index" class="col-6">
                  <div class="image-card" :class="{ 'main-image': image.is_main }">
                    <img :src="getImageUrl(image)" :alt="image.alt_text || 'Image équipement'" class="img-thumbnail">
                    <div class="image-overlay">
                      <button class="btn btn-sm btn-outline-light me-1"
                              @click="toggleMainImage(image.id)"
                              v-if="!image.is_main"
                              title="Définir comme image principale">
                        <i class="bi bi-star"></i>
                      </button>
                      <button class="btn btn-sm btn-outline-danger"
                              @click="removeImage(image.id)"
                              title="Supprimer l'image">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                    <div v-if="image.is_main" class="main-badge">
                      <span class="badge bg-primary">Principale</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-center text-muted py-3">
              <i class="bi bi-image fs-1 d-block mb-2"></i>
              <p>Aucune image</p>
            </div>

            <!-- Barre de progression -->
            <div v-if="uploading" class="mt-3">
              <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated"
                     role="progressbar"
                     :style="{ width: uploadProgress + '%' }">
                  {{ uploadProgress }}%
                </div>
              </div>
              <small class="text-muted">{{ uploadMessage }}</small>
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
import { useEquipmentStore } from '../stores/equipment'
import { useCategoryStore } from '../stores/category'

const route = useRoute()
const router = useRouter()
const equipmentStore = useEquipmentStore()
const categoryStore = useCategoryStore()

const isEdit = computed(() => !!route.params.id)
const saving = ref(false)
const uploading = ref(false)
const uploadProgress = ref(0)
const uploadMessage = ref('')
const dragover = ref(false)
const errors = ref({})
const images = ref([])

// ✅ Déclaration des refs pour les inputs
const singleImageInput = ref(null)
const multipleImageInput = ref(null)

const form = ref({
  name: '',
  category: '',
  daily_rate: 0,
  available: true,
  serial_number: ''
})

const categories = computed(() => categoryStore.categories)
const selectedCategory = computed(() => {
  return categories.value.find(c => c.slug === form.value.category)
})

// ✅ Fonctions pour déclencher les uploads
const triggerSingleUpload = () => {
  if (singleImageInput.value) {
    singleImageInput.value.click()
  }
}

const triggerMultipleUpload = () => {
  if (multipleImageInput.value) {
    multipleImageInput.value.click()
  }
}

// ✅ Récupérer l'URL de l'image
const getImageUrl = (image) => {
  if (image.urls && image.urls.thumbnail) {
    return image.urls.thumbnail
  }
  if (image.url) {
    return image.url
  }
  return '/assets/images/no-image.png'
}

// ✅ Charger les données
onMounted(async () => {
  await categoryStore.fetchActive()

  if (isEdit.value) {
    await loadEquipment()
    await loadImages()
  }
})

const loadEquipment = async () => {
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

const loadImages = async () => {
  try {
    const data = await equipmentStore.fetchImages(parseInt(route.params.id))
    images.value = data.images || []
  } catch (error) {
    console.error('Erreur chargement images:', error)
  }
}

const uploadSingleImage = async (event) => {
  const file = event.target.files[0]
  if (!file) return
  await uploadImage(file)
  event.target.value = ''
}

const handleDrop = async (event) => {
  dragover.value = false
  const files = event.dataTransfer.files
  if (files.length > 0) {
    if (files.length === 1) {
      await uploadImage(files[0])
    } else {
      await uploadMultipleImagesEvent(files)
    }
  }
}

const uploadImage = async (file) => {
  const maxSize = 5 * 1024 * 1024
  if (file.size > maxSize) {
    alert('L\'image dépasse 5MB')
    return
  }

  uploading.value = true
  uploadProgress.value = 0
  uploadMessage.value = 'Upload en cours...'

  try {
    const interval = setInterval(() => {
      if (uploadProgress.value < 90) {
        uploadProgress.value += 10
      }
    }, 200)

    const response = await equipmentStore.uploadImage(parseInt(route.params.id), file)
    clearInterval(interval)
    uploadProgress.value = 100
    uploadMessage.value = 'Upload terminé !'

    if (response.data) {
      images.value.push(response.data)
    }

    setTimeout(() => {
      uploadProgress.value = 0
      uploadMessage.value = ''
      uploading.value = false
    }, 1000)

  } catch (error) {
    uploadMessage.value = 'Erreur: ' + (error.message || 'Upload failed')
    setTimeout(() => {
      uploadMessage.value = ''
      uploading.value = false
    }, 3000)
  }
}

const uploadMultipleImages = async (event) => {
  const files = Array.from(event.target.files)
  if (files.length === 0) return
  await uploadMultipleImagesEvent(files)
  event.target.value = ''
}

const uploadMultipleImagesEvent = async (files) => {
  const maxSize = 5 * 1024 * 1024
  const validFiles = files.filter(f => f.size <= maxSize)

  if (validFiles.length !== files.length) {
    alert('Certaines images dépassent 5MB')
  }

  if (validFiles.length === 0) return

  uploading.value = true
  uploadProgress.value = 0
  uploadMessage.value = `Upload de ${validFiles.length} images...`

  try {
    let uploaded = 0
    for (const file of validFiles) {
      await equipmentStore.uploadImage(parseInt(route.params.id), file)
      uploaded++
      uploadProgress.value = Math.round((uploaded / validFiles.length) * 100)
      uploadMessage.value = `Upload ${uploaded}/${validFiles.length} images...`
    }

    uploadMessage.value = 'Tous les uploads terminés !'
    await loadImages()

    setTimeout(() => {
      uploadProgress.value = 0
      uploadMessage.value = ''
      uploading.value = false
    }, 1000)

  } catch (error) {
    uploadMessage.value = 'Erreur: ' + (error.message || 'Upload failed')
    setTimeout(() => {
      uploadMessage.value = ''
      uploading.value = false
    }, 3000)
  }
}

const removeImage = async (imageId) => {
  if (!confirm('Supprimer cette image ?')) return

  try {
    await equipmentStore.deleteImage(parseInt(route.params.id), imageId)
    images.value = images.value.filter(img => img.id !== imageId)
  } catch (error) {
    alert('Erreur lors de la suppression')
  }
}

const toggleMainImage = async (imageId) => {
  try {
    await equipmentStore.setMainImage(parseInt(route.params.id), imageId)
    images.value = images.value.map(img => ({
      ...img,
      is_main: img.id === imageId
    }))
  } catch (error) {
    alert('Erreur lors de la mise à jour')
  }
}

const saveEquipment = async () => {
  errors.value = {}
  saving.value = true

  try {
    let response
    if (isEdit.value) {
      response = await equipmentStore.update(parseInt(route.params.id), form.value)
    } else {
      response = await equipmentStore.create(form.value)
      if (response.data.id) {
        router.push(`/equipment/${response.data.id}/edit`)
        return
      }
    }
    router.push('/equipment')
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      errors.value = { general: error.message || 'Une erreur est survenue' }
    }
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.drop-zone {
  border: 2px dashed #dee2e6;
  border-radius: 0.5rem;
  padding: 1.5rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
}

.drop-zone:hover {
  border-color: #0d6efd;
  background-color: #f8f9fa;
}

.drop-zone-active {
  border-color: #0d6efd;
  background-color: #e7f1ff;
}

.image-card {
  position: relative;
  cursor: pointer;
  overflow: hidden;
  border-radius: 0.25rem;
}

.image-card img {
  width: 100%;
  height: 100px;
  object-fit: cover;
}

.image-card .image-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.image-card:hover .image-overlay {
  opacity: 1;
}

.image-card .main-badge {
  position: absolute;
  top: 5px;
  right: 5px;
}

.image-card.main-image {
  border: 2px solid #0d6efd;
}

.progress {
  height: 8px;
}
</style>