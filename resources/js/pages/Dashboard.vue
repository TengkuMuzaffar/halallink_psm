<template>
  <div class="dashboard">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
      <h1 class="mb-2 mb-md-0 fs-2 fs-md-1">Performance Dashboard</h1>
      <div class="d-flex align-items-center">
        <div class="dropdown me-3">
          <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="periodDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            {{ periodOptions.find(option => option.value === selectedPeriod).label }}
          </button>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="periodDropdown">
            <li v-for="option in periodOptions" :key="option.value">
              <a class="dropdown-item" href="#" @click.prevent="changePeriod(option.value)" :class="{ 'active': selectedPeriod === option.value }">
                {{ option.label }}
              </a>
            </li>
          </ul>
        </div>
        <button class="btn download-btn" @click="downloadReport">
          <i class="fas fa-download me-1"></i>
          Download Performance Report
        </button>
      </div>
    </div>
    
    <!-- Stats Cards Row -->
    <StatsCards :stats="companyStats" class="mb-4" />

    <!-- Company Registration Trend Chart (Full Width) -->
    <div class="row mb-4">
      <div class="col-12">
        <CompanyRegistrationChart id="company-registration-chart" :period="selectedPeriod" />
      </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
      <div class="col-lg-6 mb-4 mb-lg-0">
        <BroilerSalesPieChart id="broiler-sales-chart" :period="selectedPeriod" />
      </div>
      <div class="col-lg-6">
        <MarketLineChart id="market-line-chart" :period="selectedPeriod" />
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import StatsCards from '../components/dashboard/StatsCards.vue';
import BroilerSalesPieChart from '../components/dashboard/BroilerSalesPieChart.vue';
import MarketLineChart from '../components/dashboard/MarketLineChart.vue';
import CompanyRegistrationChart from '../components/dashboard/CompanyRegistrationChart.vue';
import DashboardService from '../services/dashboardService';
import modal from '../utils/modal';

export default {
  name: 'Dashboard',
  components: {
    StatsCards,
    BroilerSalesPieChart,
    MarketLineChart,
    CompanyRegistrationChart
  },
  setup() {
    const loading = ref(true);
    const error = ref(null);
    const isDownloading = ref(false);
    const stats = ref({
      broiler: 0,
      slaughterhouse: 0,
      sme: 0,
      logistic: 0
    });
    
    // Add period state and options
    const selectedPeriod = ref('month');
    const periodOptions = [
      { value: 'month', label: 'Month' },
      { value: 'quarter', label: 'Quarter' },
      { value: 'year', label: 'Year' }
    ];
    
    // Add period change function
    const changePeriod = (period) => {
      selectedPeriod.value = period;
    };
    
    // Transform stats for StatsCards component
    const companyStats = computed(() => {
      // Add a null check to ensure stats.value is defined
      if (!stats.value) {
        return [];
      }
      
      return [
        {
          title: 'Broiler Companies',
          count: stats.value.broiler || 0,
          icon: 'fas fa-industry',
          bgColor: 'bg-taupe'
        },
        {
          title: 'Slaughterhouse Companies',
          count: stats.value.slaughterhouse || 0,
          icon: 'fas fa-warehouse',
          bgColor: 'bg-terracotta'
        },
        {
          title: 'SME Companies',
          count: stats.value.sme || 0,
          icon: 'fas fa-store',
          bgColor: 'bg-slate-blue'
        },
        {
          title: 'Logistics Companies',
          count: stats.value.logistic || 0,
          icon: 'fas fa-truck',
          bgColor: 'bg-lavender'
        }
      ];
    });
    
    // Fetch dashboard stats
    const fetchDashboardStats = async () => {
      loading.value = true;
      error.value = null;
      
      try {
        const statsData = await DashboardService.getStats();
        stats.value = statsData;
        // console.log('Fetched dashboard stats:', statsData);
      } catch (err) {
        console.error('Error fetching dashboard data:', err);
        error.value = 'Failed to load dashboard data';
        modal.danger('Error', 'Failed to load dashboard data. Please try again.');
      } finally {
        loading.value = false;
      }
    };
    
    // Download performance report
    const downloadReport = async () => {
      isDownloading.value = true;
      
      try {
        // Create a new jsPDF instance
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({
          orientation: 'portrait',
          unit: 'mm',
          format: 'a4'
        });
        
        // Set document properties
        doc.setProperties({
          title: 'HalalLink Performance Report',
          subject: 'Dashboard Statistics',
          author: 'HalalLink',
          creator: 'HalalLink Dashboard'
        });
        
        // ===== FRONT PAGE =====
        // Add company logo
        const logoImg = new Image();
        logoImg.src = '/images/HalalLink_v1.png';
        
        // Wait for the image to load
        await new Promise((resolve) => {
          logoImg.onload = resolve;
          // Fallback if image fails to load
          setTimeout(resolve, 1000);
        });
        
        // Add logo to center of page
        const logoWidth = 100; // mm
        const logoHeight = 50; // mm
        const logoX = (210 - logoWidth) / 2; // Center on A4 page (210mm width)
        const logoY = 60; // Position from top
        doc.addImage(logoImg, 'PNG', logoX, logoY, logoWidth, logoHeight);
        
        // Add title below logo
        doc.setFontSize(24);
        doc.setTextColor(18, 53, 36); // Primary color
        doc.text('Performance Report', 105, 140, { align: 'center' });
        
        // Add date
        doc.setFontSize(14);
        doc.setTextColor(102, 102, 102); // Light text color
        const today = new Date().toLocaleDateString();
        doc.text(`Generated on: ${today}`, 105, 155, { align: 'center' });
        
        // ===== SECOND PAGE: STATS AND REGISTRATION TREND =====
        doc.addPage();
        
        // Add title
        doc.setFontSize(18);
        doc.setTextColor(18, 53, 36);
        doc.text('Company Statistics', 105, 20, { align: 'center' });
        
        // Add company stats
        doc.setFontSize(12);
        doc.setTextColor(0, 0, 0);
        let yPos = 35;
        
        if (companyStats.value) {
          // Properly format each stat for the PDF
          companyStats.value.forEach(stat => {
            doc.text(`${stat.title}: ${stat.count}`, 20, yPos);
            yPos += 7;
          });
        }
        
        // Add company registration trend chart
        const registrationChartElement = document.querySelector('#company-registration-chart .chart-container');
        if (registrationChartElement) {
          // Add descriptive text
          doc.setFontSize(14);
          doc.setTextColor(18, 53, 36);
          doc.text('Company Registration Trend', 105, 70, { align: 'center' });
          
          // Add some descriptive text below the title
          doc.setFontSize(10);
          doc.setTextColor(0, 0, 0);
          doc.text('This chart shows the trend of company registrations over time.', 105, 80, { align: 'center' });
          
          const registrationCanvas = await html2canvas(registrationChartElement, {
            scale: 2,
            useCORS: true,
            logging: false,
            willReadFrequently: true
          });
          
          const registrationImgData = registrationCanvas.toDataURL('image/png');
          const imgWidth = 170; // mm
          const imgHeight = (registrationCanvas.height * imgWidth) / registrationCanvas.width;
          
          doc.addImage(registrationImgData, 'PNG', 20, 90, imgWidth, imgHeight);
        }
        
        // ===== THIRD PAGE: MARKET LINE CHART =====
        // Capture and add the Market Line Chart (without header)
        const marketChartElement = document.querySelector('#market-line-chart .chart-container');
        if (marketChartElement) {
          // Add a new page for the first chart
          doc.addPage();
          
          // Add descriptive text at the top of the page
          doc.setFontSize(14);
          doc.setTextColor(18, 53, 36);
          doc.text('Marketplace Activity', 105, 20, { align: 'center' });
          
          // Add some descriptive text below the title
          doc.setFontSize(10);
          doc.setTextColor(0, 0, 0);
          doc.text('This chart shows the order activity over time in the marketplace.', 105, 30, { align: 'center' });
          doc.text('Higher values indicate increased trading activity.', 105, 37, { align: 'center' });
          
          // Position for the chart (leaving space for text above)
          let chartYPos = 50;
          
          const marketCanvas = await html2canvas(marketChartElement, {
            scale: 2, // Higher scale for better quality
            useCORS: true,
            logging: false,
            willReadFrequently: true
          });
          
          const marketImgData = marketCanvas.toDataURL('image/png');
          const imgWidth = 170; // mm
          const imgHeight = (marketCanvas.height * imgWidth) / marketCanvas.width;
          
          doc.addImage(marketImgData, 'PNG', 20, chartYPos, imgWidth, imgHeight);
        }
        
        // ===== FOURTH PAGE: BROILER SALES PIE CHART =====
        // Capture and add the Broiler Sales Pie Chart (without header)
        const salesChartElement = document.querySelector('#broiler-sales-chart .chart-container');
        if (salesChartElement) {
          // Add a new page for the second chart
          doc.addPage();
          
          // Add descriptive text at the top of the page
          doc.setFontSize(14);
          doc.setTextColor(18, 53, 36);
          doc.text('Broiler Sales Distribution', 105, 20, { align: 'center' });
          
          // Add some descriptive text below the title
          doc.setFontSize(10);
          doc.setTextColor(0, 0, 0);
          doc.text('This chart shows the distribution of sales across broiler companies.', 105, 30, { align: 'center' });
          doc.text('Larger segments represent companies with higher sales volumes.', 105, 37, { align: 'center' });
          
          // Position for the chart (leaving space for text above)
          let chartYPos = 50;
          
          const salesCanvas = await html2canvas(salesChartElement, {
            scale: 2,
            useCORS: true,
            logging: false,
            willReadFrequently: true
          });
          
          const salesImgData = salesCanvas.toDataURL('image/png');
          const imgWidth = 170; // mm
          const imgHeight = (salesCanvas.height * imgWidth) / salesCanvas.width;
          
          doc.addImage(salesImgData, 'PNG', 20, chartYPos, imgWidth, imgHeight);
        }
        
        // Add footer
        const pageCount = doc.getNumberOfPages();
        for (let i = 1; i <= pageCount; i++) {
          doc.setPage(i);
          doc.setFontSize(8);
          doc.setTextColor(128, 128, 128);
          doc.text('HalalLink Dashboard Report', 105, 290, { align: 'center' });
          doc.text(`Page ${i} of ${pageCount}`, 195, 290, { align: 'right' });
        }
        
        // Save the PDF
        doc.save('halallink-performance-report.pdf');
        
      } catch (error) {
        console.error('Error generating PDF:', error);
        // Fallback to the original download method if PDF generation fails
        window.location.href = '/api/dashboard/download-report';
      } finally {
        isDownloading.value = false;
      }
    };
    
    // Refresh data
    const refreshData = () => {
      fetchDashboardStats();
    };
    
    onMounted(() => {
      fetchDashboardStats();
    });
    
    return {
      loading,
      error,
      stats,
      companyStats,
      refreshData,
      downloadReport,
      isDownloading,
      selectedPeriod,
      periodOptions,
      changePeriod
    };
  }
};
</script>

<style scoped>
.download-btn {
  font-size: 0.8rem;
  padding: 6px 10px;
  background-color: var(--secondary-color);
  color: var(--primary-color);
  border: 1px solid var(--border-color);
  transition: all 0.3s ease;
}
/* Custom company type colors */
.bg-taupe {
  background-color: #B38B6D !important;
  color: white;
}

.bg-terracotta {
  background-color: #CB6D51 !important;
  color: white;
}

.bg-slate-blue {
  background-color: #6D8BB3 !important;
  color: white;
}

.bg-lavender {
  background-color: #8B6DB3 !important;
  color: white;
}
.download-btn:hover {
  background-color: var(--accent-color);
  color: white;
}

@media (min-width: 768px) {
  .download-btn {
    font-size: 0.9rem;
    padding: 8px 12px;
  }
}

.card {
  box-shadow: 0 2px 4px var(--border-color);
  border: none;
  background-color: var(--lighter-bg);
}

.card-header {
  background-color: var(--light-bg);
  border-bottom: 1px solid var(--border-color);
  color: var(--text-color);
}

.dropdown-toggle {
  font-size: 0.8rem;
  padding: 6px 10px;
  background-color: var(--lighter-bg);
  color: var(--text-color);
  border: 1px solid var(--border-color);
}

.dropdown-item.active {
  background-color: var(--primary-color);
  color: white;
}

@media (min-width: 768px) {
  .dropdown-toggle {
    font-size: 0.9rem;
    padding: 8px 12px;
  }
}


</style>