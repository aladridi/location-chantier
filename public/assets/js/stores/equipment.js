import { defineStore } from 'pinia'
import axios from 'axios'

export const useEquipmentStore = defineStore('equipment', {
    state: () => ({
        equipment: [],
        stats: {},
        loading: false,
        error: null
    }),

    actions: {
        async fetchAll() {
            this.loading = true
            try {
                const response = await axios.get('/api/equipment')
                this.equipment = response.data.data || []
            } catch (error) {
                this.error = error.message
            } finally {
                this.loading = false
            }
        },

        async fetchStats() {
            try {
                const response = await axios.get('/api/equipment/stats')
                this.stats = response.data.data || {}
            } catch (error) {
                console.error('Error fetching stats:', error)
            }
        },

        async create(data) {
            this.loading = true
            try {
                const response = await axios.post('/api/equipment', data)
                this.equipment.push(response.data.data)
                return response.data
            } catch (error) {
                this.error = error.response?.data?.error || error.message
                throw error
            } finally {
                this.loading = false
            }
        },

        async update(id, data) {
            this.loading = true
            try {
                const response = await axios.put(`/api/equipment/${id}`, data)
                const index = this.equipment.findIndex(e => e.id === id)
                if (index !== -1) {
                    this.equipment[index] = response.data.data
                }
                return response.data
            } catch (error) {
                this.error = error.response?.data?.error || error.message
                throw error
            } finally {
                this.loading = false
            }
        },

        async delete(id) {
            try {
                await axios.delete(`/api/equipment/${id}`)
                this.equipment = this.equipment.filter(e => e.id !== id)
            } catch (error) {
                this.error = error.response?.data?.error || error.message
                throw error
            }
        },

        // ✅ Upload d'une image
        async uploadImage(id, file) {
            this.loading = true
            try {
                const formData = new FormData()
                formData.append('image', file)

                const response = await axios.post(`/api/equipment/${id}/images`, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                })

                // Mettre à jour l'équipement dans le store
                const index = this.equipment.findIndex(e => e.id === id)
                if (index !== -1) {
                    this.equipment[index] = {
                        ...this.equipment[index],
                        images: response.data.data.images || []
                    }
                }

                return response.data
            } catch (error) {
                this.error = error.response?.data?.error || error.message
                throw error
            } finally {
                this.loading = false
            }
        },

        // ✅ Upload multiple d'images
        async uploadMultipleImages(id, files) {
            this.loading = true
            try {
                const formData = new FormData()
                for (let i = 0; i < files.length; i++) {
                    formData.append('images[]', files[i])
                }

                const response = await axios.post(`/api/equipment/${id}/images/multiple`, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                })

                // Mettre à jour l'équipement dans le store
                const index = this.equipment.findIndex(e => e.id === id)
                if (index !== -1) {
                    this.equipment[index] = {
                        ...this.equipment[index],
                        images: response.data.data.images || []
                    }
                }

                return response.data
            } catch (error) {
                this.error = error.response?.data?.error || error.message
                throw error
            } finally {
                this.loading = false
            }
        },

        // ✅ Suppression d'une image
        async deleteImage(equipmentId, imageId) {
            try {
                await axios.delete(`/api/equipment/images/${imageId}`)

                // Mettre à jour l'équipement dans le store
                const index = this.equipment.findIndex(e => e.id === equipmentId)
                if (index !== -1) {
                    const equipment = this.equipment[index]
                    if (equipment.images) {
                        equipment.images = equipment.images.filter(img => img.id !== imageId)
                    }
                }

                return true
            } catch (error) {
                this.error = error.response?.data?.error || error.message
                throw error
            }
        },

        // ✅ Récupérer les images d'un équipement
        async fetchImages(id) {
            try {
                const response = await axios.get(`/api/equipment/${id}/images`)
                return response.data.data
            } catch (error) {
                this.error = error.response?.data?.error || error.message
                throw error
            }
        },

        // ✅ Définir l'image principale
        async setMainImage(equipmentId, imageId) {
            try {
                const response = await axios.put(`/api/equipment/${equipmentId}/images/main`, {
                    image_id: imageId
                })
                return response.data
            } catch (error) {
                this.error = error.response?.data?.error || error.message
                throw error
            }
        },

        // ✅ Réorganiser les images
        async reorderImages(equipmentId, order) {
            try {
                const response = await axios.put(`/api/equipment/${equipmentId}/images/reorder`, {
                    order: order
                })
                return response.data
            } catch (error) {
                this.error = error.response?.data?.error || error.message
                throw error
            }
        }
    }
})