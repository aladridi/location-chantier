<template>
  <div>
    <h1 class="h3 mb-4"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>

    <div class="row g-4 mb-4">
      <div class="col-md-3" v-for="stat in stats" :key="stat.label">
        <div class="card" :class="stat.colorClass">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="card-title mb-2">{{ stat.label }}</h6>
                <h2 class="mb-0">{{ stat.value }}</h2>
              </div>
              <i class="bi fs-1 opacity-50" :class="stat.icon"></i>
            </div>
            <small class="text-white-50" v-if="stat.subtext">
              <i class="bi me-1" :class="stat.subIcon"></i> {{ stat.subtext }}
            </small>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-md-8">
        <div class="card">
          <div class="card-header bg-white">
            <h5 class="card-title mb-0"><i class="bi bi-graph-up me-2"></i>Revenus mensuels</h5>
          </div>
          <div class="card-body">
            <canvas ref="revenueChart" height="250"></canvas>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card">
          <div class="card-header bg-white">
            <h5 class="card-title mb-0"><i class="bi bi-list-task me-2"></i>Locations récentes</h5>
          </div>
          <div class="card-body p-0">
            <div class="list-group list-group-flush">
              <router-link
                  v-for="rental in recentRentals"
                  :key="rental.id"
                  :to="'/rentals/' + rental.id"
                  class="list-group-item list-group-item-action"
              >
                <div class="d-flex w-100 justify-content-between">
                  <h6 class="mb-1">{{ rental.equipment_name }}</h6>
                  <small>{{ rental.status_label }}</small>
                </div>
                <p class="mb-1 small">{{ rental.client_name }}</p>
                <small>{{ rental.date_range }}</small>
              </router-link>
              <div v-if="recentRentals.length === 0" class="list-group-item text-center text-muted py-4">
                <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                Aucune location récente
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useEquipmentStore } from '../stores/equipment'
import { useRentalStore } from '../stores/rental'
import Chart from 'chart.js/auto'

const equipmentStore = useEquipmentStore()
const rentalStore = useRentalStore()

const stats = ref([])
const recentRentals = ref([])
const revenueChart = ref(null)
let chartInstance = null

onMounted(async () => {
  await Promise.all([
    equipmentStore.fetchStats(),
    rentalStore.fetchStats(),
    rentalStore.fetchRecent(5),
    rentalStore.fetchMonthlyRevenue()
  ])

  const equipmentStats = equipmentStore.stats
  const rentalStats = rentalStore.stats

  stats.value = [
    {
      label: 'Total Équipements',
      value: equipmentStats.total || 0,
      icon: 'bi-tools',
      colorClass: 'bg-primary text-white',
      subtext: `${equipmentStats.available || 0} disponibles`,
      subIcon: 'bi-check-circle'
    },
    {
      label: 'Locations Actives',
      value: rentalStats.active || 0,
      icon: 'bi-clock',
      colorClass: 'bg-success text-white',
      subtext: `${rentalStats.overdue || 0} en retard`,
      subIcon: 'bi-exclamation-triangle'
    },
    {
      label: 'Chiffre d\'affaires',
      value: formatPrice(rentalStats.total_revenue || 0),
      icon: 'bi-euro',
      colorClass: 'bg-warning text-dark',
      subtext: `${rentalStats.total_rentals || 0} locations`,
      subIcon: 'bi-calendar'
    },
    {
      label: 'Maintenance',
      value: equipmentStats.needs_maintenance || 0,
      icon: 'bi-gear',
      colorClass: 'bg-danger text-white',
      subtext: 'Nécessite une attention',
      subIcon: 'bi-clock'
    }
  ]

  recentRentals.value = rentalStore.recent || []
  createChart()
})

watch(() => rentalStore.monthlyRevenue, () => {
  createChart()
})

const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    maximumFractionDigits: 0
  }).format(price)
}

const createChart = () => {
  if (chartInstance) {
    chartInstance.destroy()
    chartInstance = null
  }

  const monthlyData = rentalStore.monthlyRevenue || []
  if (monthlyData.length === 0) return

  const labels = monthlyData.map(item => item.month)
  const values = monthlyData.map(item => parseFloat(item.revenue))

  if (revenueChart.value) {
    chartInstance = new Chart(revenueChart.value, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Revenus (€)',
          data: values,
          backgroundColor: 'rgba(13, 110, 253, 0.6)',
          borderColor: 'rgba(13, 110, 253, 1)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: (value) => value.toLocaleString('fr-FR') + ' €'
            }
          }
        }
      }
    })
  }
}
</script>