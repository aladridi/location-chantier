import { defineStore } from 'pinia'
import axios from 'axios'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: null,
        loading: false,
        error: null,
        isAuthenticated: false
    }),

    getters: {
        isAdmin: (state) => {
            return state.user?.roles?.includes('ROLE_ADMIN') || false
        },
        username: (state) => {
            return state.user?.username || ''
        },
        email: (state) => {
            return state.user?.email || ''
        }
    },

    actions: {
        async login(identifier, password) {
            this.loading = true
            this.error = null

            try {
                const response = await axios.post('/api/auth/login', {
                    identifier,
                    password
                })

                const data = response.data.data
                this.user = data.user
                this.token = data.token
                this.isAuthenticated = true

                // Stocker dans localStorage
                localStorage.setItem('auth_token', data.token)
                localStorage.setItem('user', JSON.stringify(data.user))

                // Configurer axios pour les futures requêtes
                axios.defaults.headers.common['Authorization'] = `Bearer ${data.token}`

                return data
            } catch (error) {
                this.error = error.response?.data?.error || error.message
                throw this.error
            } finally {
                this.loading = false
            }
        },

        async register(username, email, password, passwordConfirm) {
            this.loading = true
            this.error = null

            try {
                const response = await axios.post('/api/auth/register', {
                    username,
                    email,
                    password,
                    password_confirm: passwordConfirm
                })

                const data = response.data.data
                this.user = data.user
                this.token = data.token
                this.isAuthenticated = true

                localStorage.setItem('auth_token', data.token)
                localStorage.setItem('user', JSON.stringify(data.user))
                axios.defaults.headers.common['Authorization'] = `Bearer ${data.token}`

                return data
            } catch (error) {
                this.error = error.response?.data?.error || error.message
                throw this.error
            } finally {
                this.loading = false
            }
        },

        async logout() {
            try {
                await axios.post('/api/auth/logout')
            } catch (error) {
                // Ignorer les erreurs de logout
            } finally {
                this.user = null
                this.token = null
                this.isAuthenticated = false
                localStorage.removeItem('auth_token')
                localStorage.removeItem('user')
                delete axios.defaults.headers.common['Authorization']
            }
        },

        async checkAuth() {
            const token = localStorage.getItem('auth_token')
            const userStr = localStorage.getItem('user')

            if (token && userStr) {
                try {
                    this.token = token
                    this.user = JSON.parse(userStr)
                    this.isAuthenticated = true
                    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`

                    // Vérifier que le token est toujours valide
                    await axios.get('/api/auth/me')
                } catch (error) {
                    // Token invalide, déconnecter
                    this.logout()
                }
            }
        },

        async fetchUser() {
            try {
                const response = await axios.get('/api/auth/me')
                this.user = response.data.data
                localStorage.setItem('user', JSON.stringify(this.user))
                return this.user
            } catch (error) {
                this.error = error.response?.data?.error || error.message
                throw this.error
            }
        }
    }
})