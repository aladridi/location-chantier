<template>
  <div class="image-gallery">
    <div class="row g-2">
      <div v-for="image in images" :key="image.id" class="col-4 col-md-3">
        <div class="gallery-item" :class="{ 'main': image.is_main }">
          <img :src="image.urls.thumbnail" :alt="image.alt_text || 'Image'" class="img-fluid rounded">
          <div class="gallery-overlay">
            <button class="btn btn-sm btn-outline-light"
                    v-if="!image.is_main"
                    @click="$emit('set-main', image.id)">
              <i class="bi bi-star"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger"
                    @click="$emit('delete', image.id)">
              <i class="bi bi-trash"></i>
            </button>
          </div>
          <div v-if="image.is_main" class="gallery-badge">
            <span class="badge bg-primary">Main</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  images: {
    type: Array,
    required: true
  }
})

defineEmits(['delete', 'set-main'])
</script>

<style scoped>
.gallery-item {
  position: relative;
  overflow: hidden;
  border-radius: 0.5rem;
  cursor: pointer;
}

.gallery-item img {
  width: 100%;
  height: 120px;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.gallery-item:hover img {
  transform: scale(1.05);
}

.gallery-item .gallery-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s ease;
  gap: 0.5rem;
}

.gallery-item:hover .gallery-overlay {
  opacity: 1;
}

.gallery-item .gallery-badge {
  position: absolute;
  top: 8px;
  right: 8px;
}

.gallery-item.main {
  border: 2px solid #0d6efd;
}
</style>