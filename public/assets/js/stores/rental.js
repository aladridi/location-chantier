import { defineStore } from 'pinia'
import axios from 'axios'

export const useRentalStore = defineStore('rental', {
    state: () => ({
        rentals: [],
        stats: {},
        recent: [],
        monthlyRevenue: [],
        loading: false,
        error: null
    }),

    actions: {
        async fetchAll() {
            this.loading = true
            try {
                const response = await axios.get('/api/rentals')
                this.rentals = response.data.data || []
            } catch (error) {
                this.error = error.message
            } finally {
                this.loading = false
            }
        },

        async fetchStats() {
            try {
                const response = await axios.get('/api/rentals/stats')
                this.stats = response.data.data || {}
            } catch (error) {
                console.error('Error fetching stats:', error)
            }
        },

        async fetchRecent(limit = 5) {
            try {
                const response = await axios.get(`/api/rentals/recent?limit=${limit}`)
                this.recent = response.data.data || []
            } catch (error) {
                console.error('Error fetching recent rentals:', error)
            }
        },

        async fetchMonthlyRevenue() {
            try {
                const response = await axios.get('/api/rentals/monthly-revenue')
                this.monthlyRevenue = response.data.data || []
            } catch (error) {
                console.error('Error fetching monthly revenue:', error)
            }
        },

        async create(data) {
            this.loading = true
            try {
                const response = await axios.post('/api/rentals', data)
                this.rentals.push(response.data.data)
                return response.data
            } catch (error) {
                this.error = error.response?.data?.error || error.message
                throw error
            } finally {
                this.loading = false
            }
        },

        async returnRental(id) {
            try {
                const response = await axios.post(`/api/rentals/${id}/return`)
                const index = this.rentals.findIndex(r => r.id === id)
                if (index !== -1) {
                    this.rentals[index] = response.data.data
                }
                return response.data
            } catch (error) {
                this.error = error.response?.data?.error || error.message
                throw error
            }
        }
    }
})