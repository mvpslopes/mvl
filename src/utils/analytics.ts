// Utilitário para buscar dados do Google Analytics via API
// Esta é uma alternativa mais simples que não requer OAuth no backend

export interface AnalyticsData {
  totalUsers: number;
  totalSessions: number;
  pageViews: number;
  bounceRate: number;
  topPages: Array<{ page: string; views: number }>;
  trafficSources: Array<{ source: string; sessions: number }>;
}

// Função para buscar dados do Analytics via API do servidor
export async function fetchAnalyticsData(days: number = 7): Promise<AnalyticsData> {
  try {
    const response = await fetch(`/api/analytics.php?days=${days}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
      },
    });

    const data = await response.json();

    if (!response.ok || !data.success) {
      throw new Error(data.message || 'Erro ao carregar dados');
    }

    return data.data;
  } catch (error) {
    console.error('Erro ao buscar dados do Analytics:', error);
    throw error;
  }
}

// Configurações do Google Analytics
export const GA_CONFIG = {
  propertyId: '13183308243',
  measurementId: 'G-6ZCVW4LQG9',
};

