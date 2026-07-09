<template>
  <div class="auth-page">
    <div class="container">
      <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-5 col-lg-4">
          <div class="card auth-card">
            <div class="card-body p-4">
              <div class="text-center mb-4">
                <i class="bi bi-tools auth-icon"></i>
                <h4 class="mt-2 fw-bold">Location Chantier</h4>
                <p class="text-muted">Connectez-vous à votre compte</p>
              </div>

              <div v-if="error" class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                {{ error }}
                <button type="button" class="btn-close" @click="error = null"></button>
              </div>

              <form @submit.prevent="handleLogin">
                <div class="mb-3">
                  <label for="identifier" class="form-label">Email ou nom d'utilisateur</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" id="identifier"
                           v-model="form.identifier" placeholder="Email ou nom d'utilisateur" required>
                  </div>
                </div>

                <div class="mb-3">
                  <label for="password" class="form-label">Mot de passe</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password"
                           v-model="form.password" placeholder="Votre mot de passe" required>
                  </div>
                </div>

                <div class="mb-3 d-flex justify-content-between align-items-center">
                  <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="remember" v-model="form.remember">
                    <label class="form-check-label" for="remember">Se souvenir de moi</label>
                  </div>
                  <router-link to="/forgot-password" class="text-decoration-none small">
                    Mot de passe oublié ?
                  </router-link>
                </div>

                <button type="submit" class="btn btn-primary w-100" :disabled="loading">
                  <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                  <i v-else class="bi bi-box-arrow-in-right me-2"></i>
                  Se connecter
                </button>
              </form>

              <hr class="my-4">

              <p class="text-center mb-0">
                Pas encore de compte ?
                <router-link to="/register" class="text-decoration-none fw-bold">S'inscrire</router-link>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = ref({
  identifier: '',
  password: '',
  remember: false
})

const loading = ref(false)
const error = ref(null)

const handleLogin = async () => {
  error.value = null
  loading.value = true

  try {
    await authStore.login(form.value.identifier, form.value.password)
    router.push('/')
  } catch (err) {
    error.value = err
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (authStore.isAuthenticated) {
    router.push('/')
  }
})
</script>

<style scoped>
.auth-page {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
}

.auth-card {
  border-radius: 1rem;
  box-shadow: 0 1rem 3rem rgba(0,0,0,0.3);
  border: none;
}

.auth-icon {
  font-size: 2.5rem;
  color: #667eea;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  padding: 0.75rem;
  font-weight: 600;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
}

.btn-primary:disabled {
  background: #6c757d;
  transform: none;
  box-shadow: none;
}

.form-control:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.input-group-text {
  background-color: #f8f9fa;
}
</style>