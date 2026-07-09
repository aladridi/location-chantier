<template>
  <div class="auth-page">
    <div class="container">
      <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-6 col-lg-5">
          <div class="card auth-card">
            <div class="card-body p-4">
              <div class="text-center mb-4">
                <i class="bi bi-person-plus auth-icon"></i>
                <h4 class="mt-2 fw-bold">Créer un compte</h4>
                <p class="text-muted">Inscrivez-vous pour commencer</p>
              </div>

              <div v-if="error" class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                {{ error }}
                <button type="button" class="btn-close" @click="error = null"></button>
              </div>

              <form @submit.prevent="handleRegister">
                <div class="mb-3">
                  <label for="username" class="form-label">Nom d'utilisateur</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" id="username"
                           v-model="form.username" placeholder="Choisissez un pseudo" required>
                  </div>
                </div>

                <div class="mb-3">
                  <label for="email" class="form-label">Email</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" id="email"
                           v-model="form.email" placeholder="votre@email.com" required>
                  </div>
                </div>

                <div class="mb-3">
                  <label for="password" class="form-label">Mot de passe</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password"
                           v-model="form.password" placeholder="Au moins 8 caractères" required>
                  </div>
                </div>

                <div class="mb-3">
                  <label for="password_confirm" class="form-label">Confirmer le mot de passe</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password_confirm"
                           v-model="form.password_confirm" placeholder="Retapez votre mot de passe" required>
                  </div>
                </div>

                <button type="submit" class="btn btn-primary w-100" :disabled="loading">
                  <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                  <i v-else class="bi bi-person-plus me-2"></i>
                  S'inscrire
                </button>
              </form>

              <hr class="my-4">

              <p class="text-center mb-0">
                Déjà un compte ?
                <router-link to="/login" class="text-decoration-none fw-bold">Se connecter</router-link>
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
  username: '',
  email: '',
  password: '',
  password_confirm: ''
})

const loading = ref(false)
const error = ref(null)

const handleRegister = async () => {
  error.value = null

  if (form.value.password !== form.value.password_confirm) {
    error.value = 'Les mots de passe ne correspondent pas'
    return
  }

  if (form.value.password.length < 8) {
    error.value = 'Le mot de passe doit faire au moins 8 caractères'
    return
  }

  loading.value = true

  try {
    await authStore.register(
        form.value.username,
        form.value.email,
        form.value.password,
        form.value.password_confirm
    )
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