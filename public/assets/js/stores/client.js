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
            this.error = null
            try {
                const clients = []
                let offset = 0
                let total = 0
                do {
                    const response = await axios.get('/api/clients', { params: { limit: 100, offset } })
                    const page = response.data.data || []
                    total = Number(response.data.pagination?.total ?? page.length)
                    clients.push(...page)
                    offset += page.length
                    if (!page.length) break
                } while (offset < total)
                this.clients = clients
            } catch (error) {
                this.error = error.response?.data?.error || error.message
                throw error
            } finally {
                this.loading = false
            }
        },

        async fetchOne(id) {
            this.loading = true
            this.error = null
            try {
                const response = await axios.get(`/api/clients/${id}`)
                return response.data.data.client
            } catch (error) {
                this.error = error.response?.data?.error || error.message
                throw error
            } finally {
                this.loading = false
            }
        },

        async create(data) {
            this.loading = true
            this.error = null
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
            this.error = null
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
            this.error = null
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