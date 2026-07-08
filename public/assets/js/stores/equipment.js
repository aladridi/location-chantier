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
                this.equipment = response.data.data
            } catch (error) {
                this.error = error.message
            } finally {
                this.loading = false
            }
        },

        async fetchStats() {
            try {
                const response = await axios.get('/api/equipment/stats')
                this.stats = response.data.data
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
        }
    }
})