import { defineStore } from 'pinia'
import axios from 'axios'

export const useClientStore = defineStore('client', {
    state: () => ({
        clients: [],
        loading: false,
        error: null
    }),

    actions: {
        async fetchAll() {
            this.loading = true
            try {
                const response = await axios.get('/api/clients')
                this.clients = response.data.data || []
            } catch (error) {
                this.error = error.message
            } finally {
                this.loading = false
            }
        },

        async create(data) {
            this.loading = true
            try {
                const response = await axios.post('/api/clients', data)
                this.clients.push(response.data.data)
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
                const response = await axios.put(`/api/clients/${id}`, data)
                const index = this.clients.findIndex(c => c.id === id)
                if (index !== -1) {
                    this.clients[index] = response.data.data
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
                await axios.delete(`/api/clients/${id}`)
                this.clients = this.clients.filter(c => c.id !== id)
            } catch (error) {
                this.error = error.response?.data?.error || error.message
                throw error
            }
        }
    }
})