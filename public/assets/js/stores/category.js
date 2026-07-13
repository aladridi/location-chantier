import { defineStore } from 'pinia'
import axios from 'axios'

export const useCategoryStore = defineStore('category', {
    state: () => ({
        categories: [],
        loading: false,
        error: null
    }),

    getters: {
        activeCategories: (state) => {
            return state.categories.filter(c => c.is_active)
        },
        getCategoryBySlug: (state) => (slug) => {
            return state.categories.find(c => c.slug === slug)
        },
        getCategoryById: (state) => (id) => {
            return state.categories.find(c => c.id === id)
        }
    },

    actions: {
        async fetchAll() {
            this.loading = true
            try {
                const response = await axios.get('/api/categories')
                this.categories = response.data.data || []
                return this.categories
            } catch (error) {
                this.error = error.message
                console.error('Erreur chargement catégories:', error)
            } finally {
                this.loading = false
            }
        },

        async fetchActive() {
            this.loading = true
            try {
                const response = await axios.get('/api/categories/active')
                this.categories = response.data.data || []
                return this.categories
            } catch (error) {
                this.error = error.message
                console.error('Erreur chargement catégories actives:', error)
            } finally {
                this.loading = false
            }
        },

        async create(data) {
            this.loading = true
            try {
                const response = await axios.post('/api/categories', data)
                this.categories.push(response.data.data)
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
                const response = await axios.put(`/api/categories/${id}`, data)
                const index = this.categories.findIndex(c => c.id === id)
                if (index !== -1) {
                    this.categories[index] = response.data.data
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
                await axios.delete(`/api/categories/${id}`)
                this.categories = this.categories.filter(c => c.id !== id)
            } catch (error) {
                this.error = error.response?.data?.error || error.message
                throw error
            }
        },

        async reorder(orders) {
            try {
                await axios.post('/api/categories/reorder', { orders })
            } catch (error) {
                this.error = error.response?.data?.error || error.message
                throw error
            }
        }
    }
})