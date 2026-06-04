<template>
  <div class="container">
    <header>
      <div>
        <h1>📊 Campaign Live Dashboard</h1>
        <p style="color: #64748b; margin: 0.6rem 0 0 0; font-size: 1.1rem;">
          Real-time Ad Tech ingestion metrics via WebSockets
        </p>
      </div>
    </header>

    <div class="grid">
      <div class="card">
        <h2>Campaign Performance (Total Impressions)</h2>
        <div class="chart-container">
          <Bar :data="barData" :options="chartOptions" />
        </div>
      </div>

      <div class="card">
        <h2>Browser Breakdown</h2>
        <div class="chart-container">
          <Doughnut :data="doughnutData" :options="chartOptions" />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Bar, Doughnut } from 'vue-chartjs';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  ArcElement
} from 'chart.js';

// Explicitly register Chart.js modules inside the npm context
ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);

export default {
  components: { Bar, Doughnut },
  setup() {
    const campaignsData = ref([]);
    const browsersData = ref([]);
    let ws = null;

    // Fetch historical data snapshot from backend API
    const fetchData = async () => {
      try {
        const response = await fetch('http://localhost:8080/api.php');
        const result = await response.json();
        if (result.status === 'success') {
          campaignsData.value = result.data.campaigns;
          browsersData.value = result.data.browsers;
        }
      } catch (error) {
        console.error("Error fetching analytics metrics:", error);
      }
    };

    // Initialize real-time WebSocket connection listener
    const initWebSocket = () => {
      ws = new WebSocket('ws://localhost:8085');

      ws.onmessage = (event) => {
        const data = JSON.parse(event.data);

        if (data.event === 'impression_received') {
          // Update local campaigns array reactively
          const campaign = campaignsData.value.find(c => c.campaign_id === data.campaign_id);
          if (campaign) {
            campaign.total = parseInt(campaign.total) + 1;
          } else {
            campaignsData.value.push({ campaign_id: data.campaign_id, total: 1 });
          }

          // Update local browsers array reactively
          const browser = browsersData.value.find(b => b.browser === data.browser);
          if (browser) {
            browser.total = parseInt(browser.total) + 1;
          } else {
            browsersData.value.push({ browser: data.browser, total: 1 });
          }
        }
      };
    };

    onMounted(() => {
      fetchData();
      initWebSocket();
    });

    onBeforeUnmount(() => {
      if (ws) ws.close();
    });

    // Computed property maps data reactive updates directly to the Bar layout
    const barData = computed(() => ({
      labels: campaignsData.value.map(c => c.campaign_id),
      datasets: [{
        label: 'Impressions',
        data: campaignsData.value.map(c => c.total),
        backgroundColor: '#0284c7', // Professional Blue for campaigns
        borderRadius: 8
      }]
    }));

    // Computed property maps data reactive updates directly to the Doughnut ring
    const doughnutData = computed(() => {
      // Define the exact brand color mapping rules
      const colorMap = {
        'Chrome': '#16a34a',  // Green (Tailwind green-600)
        'Firefox': '#ea580c', // Orange (Tailwind orange-600)
        'Safari': '#2563eb',  // Blue (Tailwind blue-600)
        'Opera': '#dc2626',   // Red (Tailwind red-600)
        'Edge': '#7c3aed',    // Purple (Tailwind violet-600)
        'Other': '#64748b'    // Gray for unrecognized agents
      };

      // Map over the current active browser labels to build a matching color array
      const dynamicColors = browsersData.value.map(b => colorMap[b.browser] || colorMap['Other']);

      return {
        labels: browsersData.value.map(b => b.browser),
        datasets: [{
          data: browsersData.value.map(b => b.total),
          backgroundColor: dynamicColors,
          borderWidth: 4,
          borderColor: '#ffffff',
          hoverOffset: 15
        }]
      };
    });

    const chartOptions = {
      responsive: true,
      maintainAspectRatio: false
    };

    return {
      barData,
      doughnutData,
      chartOptions
    };
  }
};
</script>