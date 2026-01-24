import { useCallback, useEffect, useState } from 'react';
import { 
  Users, Eye, TrendingUp,
  Settings, Shield, LogOut, RefreshCw,
  MousePointerClick, Clock, Activity, X, ArrowRight,
  FileText, Clock as ClockIcon,
  Search, Filter, MoreVertical,
  HelpCircle, UserCheck, TrendingDown as TrendingDownIcon
} from 'lucide-react';
import logoMvlBranco from '../../logo/logo_mvl-2_branco.png';

interface AnalyticsData {
  totalUsers: number;
  totalSessions: number;
  pageViews: number;
  bounceRate: number;
  topPages: Array<{ page: string; views: number; avgTime?: string }>;
  trafficSources: Array<{ source: string; sessions: number }>;
  uniqueVisitors?: number;
  totalClicks?: number;
  avgTimeOnPage?: string;
  avgSessionDuration?: string;
  conversionRate?: number;
  pagesPerSession?: number;
  peakHours?: Array<{ hour: string; value: number }>;
  activityByDay?: Array<{ day: string; value: number }>;
  visitorsOverTime?: Array<{ date: string; value: number }>;
  devices?: Array<{ device: string; sessions: number; percentage: number }>;
  browsers?: Array<{ browser: string; sessions: number }>;
  operatingSystems?: Array<{ os: string; sessions: number }>;
  entryPages?: Array<{ page: string; entries: number }>;
  exitPages?: Array<{ page: string; exits: number }>;
  countries?: Array<{ country: string; sessions: number; views: number }>;
  cities?: Array<{ city: string; sessions: number; views: number }>;
  onlineNow?: number;
}

export default function Dashboard() {
  const [analytics, setAnalytics] = useState<AnalyticsData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [dateRange, setDateRange] = useState('7');
  const [mounted, setMounted] = useState(false);
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [activeFilter, setActiveFilter] = useState('All sessions');
  const [pageFilter, setPageFilter] = useState('View all');
  const [searchQuery, setSearchQuery] = useState('');
  const [activeSection, setActiveSection] = useState('statistics');
  const [helpTooltip, setHelpTooltip] = useState<string | null>(null);
  const [isMockData, setIsMockData] = useState(false);
  const [users, setUsers] = useState<Array<{id: number; nome: string; email: string; perfis: string[]; isRoot: boolean}>>([]);
  const [loadingUsers, setLoadingUsers] = useState(false);
  const [selectedUserId, setSelectedUserId] = useState<number | null>(null);
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [passwordError, setPasswordError] = useState('');
  const [passwordSuccess, setPasswordSuccess] = useState('');

  const checkAuth = useCallback(() => {
    const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
    const role = localStorage.getItem('user_role') || sessionStorage.getItem('user_role');

    if (!token || role !== 'root') {
      setError('You are not authenticated or do not have permission to access this page.');
      setLoading(false);
      setTimeout(() => {
        window.location.href = '/login';
      }, 3000);
      return false;
    }
    return true;
  }, []);

  const loadAnalytics = useCallback(async () => {
    setLoading(true);
    setError('');

    try {
      const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');

      const apiPaths = [
        `/api/analytics.php?days=${dateRange}`,
        `https://mvlopes.com.br/api/analytics.php?days=${dateRange}`,
        `./api/analytics.php?days=${dateRange}`,
      ];

      type AnalyticsApiResponse = {
        success?: boolean;
        message?: string;
        note?: string;
        data?: Partial<AnalyticsData>;
      };

      let response: Response | null = null;
      let data: AnalyticsApiResponse | null = null;

      const headers: HeadersInit = {
        'Content-Type': 'application/json',
      };

      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }

      for (const apiPath of apiPaths) {
        try {
          response = await fetch(apiPath, {
            method: 'GET',
            headers: headers,
            credentials: 'include',
          });

          if (response.ok) {
            data = (await response.json()) as AnalyticsApiResponse;
            break;
          } else {
            try {
              data = (await response.json()) as AnalyticsApiResponse;
              if (data?.message) throw new Error(data.message);
            } catch {
              /* ignore JSON parse errors */
            }
          }
        } catch {
          continue;
        }
      }

      if (!response || !data) {
        throw new Error('Unable to connect to server.');
      }

      if (!response.ok || !data.success) {
        throw new Error(data.message || 'Error loading data');
      }

      const raw = (data.data ?? {}) as Partial<AnalyticsData>;

      // Verificar se os dados são mockados
      if (data.note && data.note.includes('mockados')) {
        setIsMockData(true);
        console.warn('⚠️ Dados mockados detectados. Configure use_mock_data => false em api/config.php para usar dados reais do Google Analytics.');
      } else {
        setIsMockData(false);
      }

      // Log para debug
      console.log('📊 Dados recebidos da API:', {
        totalSessions: raw.totalSessions,
        totalUsers: raw.totalUsers,
        pageViews: raw.pageViews,
        hasPeakHours: !!raw.peakHours?.length,
        hasActivityByDay: !!raw.activityByDay?.length,
        hasDevices: !!raw.devices?.length,
        hasBrowsers: !!raw.browsers?.length,
        hasCountries: !!raw.countries?.length,
        hasCities: !!raw.cities?.length,
        note: data.note
      });

      // Função para traduzir países
      const translateCountry = (country: string): string => {
        const translations: { [key: string]: string } = {
          'Brazil': 'Brasil',
          'United States': 'Estados Unidos',
          'France': 'França',
          'Canada': 'Canadá',
          'Spain': 'Espanha',
          'Poland': 'Polônia',
          'Luxembourg': 'Luxemburgo',
          'United Kingdom': 'Reino Unido',
          'Germany': 'Alemanha',
          'Italy': 'Itália',
          'Portugal': 'Portugal',
          'Argentina': 'Argentina',
          'Mexico': 'México',
          'Chile': 'Chile',
          'Colombia': 'Colômbia',
        };
        return translations[country] || country;
      };

      // Função para traduzir cidades
      const translateCity = (city: string): string => {
        // Formato: "Cidade(País)" ou "Cidade (País)"
        const cityMatch = city.match(/^(.+?)\s*\((.+?)\)$/);
        if (cityMatch) {
          const cityName = cityMatch[1].trim();
          const country = cityMatch[2].trim();
          const translatedCountry = translateCountry(country);
          return `${cityName} (${translatedCountry})`;
        }
        // Se não tiver parênteses, retorna como está
        return city;
      };

      // Função para traduzir dias da semana
      const translateDay = (day: string): string => {
        const translations: { [key: string]: string } = {
          'Sunday': 'Domingo',
          'Monday': 'Segunda-feira',
          'Tuesday': 'Terça-feira',
          'Wednesday': 'Quarta-feira',
          'Thursday': 'Quinta-feira',
          'Friday': 'Sexta-feira',
          'Saturday': 'Sábado',
        };
        return translations[day] || day;
      };

      // Processar dados reais sem fallbacks de mockup
      const processedData: AnalyticsData = {
        totalUsers: raw.totalUsers ?? 0,
        totalSessions: raw.totalSessions ?? 0,
        pageViews: raw.pageViews ?? 0,
        bounceRate: raw.bounceRate ?? 0,
        topPages: raw.topPages ?? [],
        trafficSources: raw.trafficSources ?? [],
        uniqueVisitors: raw.totalUsers || 0,
        totalClicks: raw.totalClicks || 0,
        avgTimeOnPage: raw.avgTimeOnPage || '0s',
        avgSessionDuration: raw.avgSessionDuration || '0s',
        conversionRate: raw.conversionRate || 0,
        pagesPerSession: raw.pagesPerSession || 0,
        onlineNow: raw.onlineNow || 0,
        peakHours: raw.peakHours || [],
        activityByDay: raw.activityByDay
          ? raw.activityByDay.map((item: { day: string; value: number }) => ({
              ...item,
              day: translateDay(item.day),
            }))
          : [],
        visitorsOverTime: raw.visitorsOverTime || [],
        devices: raw.devices || [],
        browsers: raw.browsers || [],
        operatingSystems: raw.operatingSystems || [],
        entryPages: raw.entryPages || [],
        exitPages: raw.exitPages || [],
        countries: raw.countries
          ? raw.countries.map((item: { country: string; sessions: number; views: number }) => ({
              ...item,
              country: translateCountry(item.country),
            }))
          : [],
        cities: raw.cities
          ? raw.cities.map((item: { city: string; sessions: number; views: number }) => ({
              ...item,
              city: translateCity(item.city),
            }))
          : [],
      };

      setAnalytics(processedData);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Error loading Analytics data');
      console.error('Error loading analytics:', err);
    } finally {
      setLoading(false);
    }
  }, [dateRange]);

  useEffect(() => {
    console.log('🚀 Dashboard montado');
    setMounted(true);

    const t = setTimeout(() => {
      const isAuthenticated = checkAuth();
      if (isAuthenticated) {
        loadAnalytics();
      } else {
        setLoading(false);
      }
    }, 200);

    return () => clearTimeout(t);
  }, [checkAuth, loadAnalytics]);

  useEffect(() => {
    if (mounted) loadAnalytics();
  }, [mounted, loadAnalytics]);

  // Fechar tooltip ao clicar fora
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (helpTooltip && !(event.target as HTMLElement).closest('.help-tooltip-container')) {
        setHelpTooltip(null);
      }
    };

    if (helpTooltip) {
      document.addEventListener('mousedown', handleClickOutside);
      return () => document.removeEventListener('mousedown', handleClickOutside);
    }
  }, [helpTooltip]);



  const handleLogout = () => {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user_role');
    localStorage.removeItem('user_name');
    sessionStorage.removeItem('auth_token');
    sessionStorage.removeItem('user_role');
    sessionStorage.removeItem('user_name');
    window.location.href = '/login';
  };

  const loadUsers = async () => {
    setLoadingUsers(true);
    setPasswordError('');
    setPasswordSuccess('');
    
    try {
      const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
      console.log('🔑 Carregando usuários com token:', token ? 'presente' : 'ausente');
      
      const response = await fetch('/api/users.php', {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        credentials: 'include' // Importante para enviar cookies de sessão
      });

      console.log('📡 Resposta da API users.php:', response.status, response.statusText);
      
      const data = await response.json();
      console.log('📦 Dados recebidos:', data);
      
      if (data.success) {
        setUsers(data.users || []);
        console.log('✅ Usuários carregados:', data.users?.length || 0);
      } else {
        const errorMsg = data.message || 'Erro ao carregar usuários';
        console.error('❌ Erro:', errorMsg, data.debug || '');
        setPasswordError(errorMsg + (data.debug ? ` (Debug: ${JSON.stringify(data.debug)})` : ''));
      }
    } catch (err) {
      console.error('❌ Erro ao carregar usuários:', err);
      setPasswordError('Erro ao carregar usuários: ' + (err instanceof Error ? err.message : 'Erro desconhecido'));
    } finally {
      setLoadingUsers(false);
    }
  };

  const handleChangePassword = async () => {
    setPasswordError('');
    setPasswordSuccess('');

    if (!selectedUserId) {
      setPasswordError('Selecione um usuário');
      return;
    }

    if (!newPassword || newPassword.length < 6) {
      setPasswordError('A senha deve ter pelo menos 6 caracteres');
      return;
    }

    if (newPassword !== confirmPassword) {
      setPasswordError('As senhas não coincidem');
      return;
    }

    try {
      const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
      const response = await fetch('/api/change-password.php', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          userId: selectedUserId,
          newPassword: newPassword
        })
      });

      const data = await response.json();
      
      if (data.success) {
        setPasswordSuccess('Senha alterada com sucesso!');
        setNewPassword('');
        setConfirmPassword('');
        setSelectedUserId(null);
      } else {
        setPasswordError(data.message || 'Erro ao alterar senha');
      }
    } catch (err) {
      setPasswordError('Erro ao alterar senha');
      console.error('Erro ao alterar senha:', err);
    }
  };

  useEffect(() => {
    if (activeSection === 'settings') {
      loadUsers();
    }
  }, [activeSection]);

  const userName = localStorage.getItem('user_name') || sessionStorage.getItem('user_name') || 'User';

  const formatTime = (timeStr: string) => {
    if (!timeStr) return '0s';
    return timeStr;
  };

  const formatPercentage = (value: number, total: number) => {
    if (!total || total === 0) return '0.0';
    return ((value / total) * 100).toFixed(1);
  };

  // Filtrar páginas baseado na busca
  const filteredPages = analytics?.topPages?.filter(page => 
    page.page.toLowerCase().includes(searchQuery.toLowerCase())
  ) || [];

  if (!mounted || (loading && !analytics)) {
    return (
      <div className="min-h-screen bg-background text-foreground flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-brand mx-auto mb-4"></div>
          <p className="text-muted-foreground">Carregando dados...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-background text-foreground flex">
      {/* Sidebar - Design Moderno */}
      <aside
        className={`${sidebarOpen ? 'w-64' : 'w-20'} bg-card transition-all duration-300 flex flex-col border-r border-white/10`}
      >
        {/* Logo e Header */}
        <div className="p-4 border-b border-white/10">
          <div className={`flex items-center ${!sidebarOpen ? 'justify-center' : 'justify-between'}`}>
            <img 
              src={logoMvlBranco} 
              alt="Logo" 
              className={`${sidebarOpen ? 'w-16 h-16' : 'w-12 h-12'} object-contain`}
            />
            {sidebarOpen && (
              <button 
                onClick={() => setSidebarOpen(false)} 
                className="text-muted-foreground hover:text-foreground transition-colors"
              >
                <X size={18} />
              </button>
            )}
          </div>
          {!sidebarOpen && (
            <div className="mt-3 flex justify-center">
              <button 
                onClick={() => setSidebarOpen(true)} 
                className="text-muted-foreground hover:text-foreground transition-colors"
              >
                <ArrowRight size={18} />
              </button>
            </div>
          )}
        </div>

        {/* Navigation */}
        <nav className="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
          <button
            onClick={() => setActiveSection('statistics')}
            className={`w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-colors ${
              activeSection === 'statistics'
                ? 'bg-white/5 text-foreground border border-white/10'
                : 'text-muted-foreground hover:bg-white/5 hover:text-foreground'
            }`}
          >
            <Activity size={18} />
            {sidebarOpen && <span className="text-sm">Estatísticas</span>}
          </button>
          <button
            onClick={() => setActiveSection('users')}
            className={`w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-colors ${
              activeSection === 'users'
                ? 'bg-white/5 text-foreground border border-white/10'
                : 'text-muted-foreground hover:bg-white/5 hover:text-foreground'
            }`}
          >
            <Users size={18} />
            {sidebarOpen && <span className="text-sm">Usuários</span>}
          </button>
          <button
            onClick={() => setActiveSection('settings')}
            className={`w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-colors ${
              activeSection === 'settings'
                ? 'bg-white/5 text-foreground border border-white/10'
                : 'text-muted-foreground hover:bg-white/5 hover:text-foreground'
            }`}
          >
            <Settings size={18} />
            {sidebarOpen && <span className="text-sm">Configurações</span>}
          </button>
        </nav>

        {/* User Section */}
        <div className="p-4 border-t border-white/10">
          <div className={`${!sidebarOpen && 'flex justify-center'}`}>
            <button
              onClick={handleLogout}
              className="flex items-center gap-2 w-full px-3 py-2 rounded-lg text-muted-foreground hover:bg-white/5 hover:text-foreground transition-colors"
            >
              <LogOut size={18} />
              {sidebarOpen && <span className="text-sm">Sair</span>}
            </button>
          </div>
        </div>
      </aside>

      {/* Main Content - Design Moderno */}
      <main className="flex-1 overflow-x-hidden bg-background">
        <div className="p-6">
          {error && (
            <div className="mb-6 bg-red-900/20 border border-red-500/30 text-red-200 px-4 py-3 rounded-lg">
              <p className="font-semibold">⚠️ Erro:</p>
              <p className="mt-1 text-sm">{error}</p>
            </div>
          )}

          {/* Aviso sobre dados mockados */}
          {isMockData && (
            <div className="mb-6 bg-yellow-900/20 border border-yellow-500/30 text-yellow-200 px-4 py-3 rounded-lg">
              <p className="font-semibold">ℹ️ Informação:</p>
              <p className="mt-1 text-sm">
                Os dados exibidos são de demonstração (mockados). Para ver dados reais do Google Analytics, configure{' '}
                <code className="bg-black/30 px-2 py-1 rounded">use_mock_data {'=>'} false</code> em{' '}
                <code className="bg-black/30 px-2 py-1 rounded">api/config.php</code>
              </p>
            </div>
          )}

          {/* Statistics Section */}
          {activeSection === 'statistics' && (
            <div className="space-y-6">
              {/* Welcome Header */}
              <div className="mb-6">
                <h1 className="text-2xl font-extrabold text-foreground mb-2">Estatísticas</h1>
                <p className="text-muted-foreground">Bem-vindo de volta, {userName}</p>
              </div>

              {/* Period Selector */}
              <div className="mb-6 flex items-center gap-4">
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                  <div className="w-2 h-2 bg-green-400 rounded-full"></div>
                  <span>{analytics?.onlineNow || 1} visitante online agora</span>
                </div>
                <div className="flex gap-2">
                  {['Hoje', '7 dias', '30 dias', '90 dias'].map((period) => (
                    <button
                      key={period}
                      onClick={() => {
                        const days = period === 'Hoje' ? '1' : period === '7 dias' ? '7' : period === '30 dias' ? '30' : '90';
                        setDateRange(days);
                      }}
                      className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                        (period === 'Hoje' && dateRange === '1') ||
                        (period === '7 dias' && dateRange === '7') ||
                        (period === '30 dias' && dateRange === '30') ||
                        (period === '90 dias' && dateRange === '90')
                          ? 'bg-white/10 text-foreground border border-white/15'
                          : 'bg-black/20 text-muted-foreground hover:text-foreground hover:bg-white/5 border border-white/10'
                      }`}
                    >
                      {period}
                    </button>
                  ))}
                </div>
              </div>

              {/* Statistics Cards Grid */}
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                {/* Unique Visitors */}
                <div className="bg-card rounded-2xl p-6 border border-white/10 relative help-tooltip-container shadow-sm">
                  <button
                    onClick={() => setHelpTooltip(helpTooltip === 'uniqueVisitors' ? null : 'uniqueVisitors')}
                    className="absolute top-4 right-4 text-muted-foreground hover:text-foreground transition-colors"
                  >
                    <HelpCircle size={16} />
                  </button>
                  {helpTooltip === 'uniqueVisitors' && (
                    <div className="absolute top-10 right-0 z-10 bg-black/80 backdrop-blur border border-white/10 rounded-xl p-3 shadow-lg max-w-xs">
                      <p className="text-xs text-white/70">
                        <strong className="text-white">Visitantes Únicos:</strong> Número de pessoas diferentes que visitaram seu site no período selecionado. Cada pessoa é contada apenas uma vez, mesmo que visite várias vezes.
                      </p>
                    </div>
                  )}
                  <div className="flex items-center gap-3 mb-3">
                    <div className="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                      <UserCheck className="text-blue-400" size={20} />
                    </div>
                    <h3 className="text-sm font-semibold text-muted-foreground">Visitantes Únicos</h3>
                  </div>
                  <p className="text-3xl font-extrabold text-foreground mb-1">
                    {analytics?.uniqueVisitors || analytics?.totalUsers || 0}
                  </p>
                  <p className="text-sm text-muted-foreground">Total de visitas: {analytics?.totalSessions || 0}</p>
                </div>

                {/* Page Views */}
                <div className="bg-card rounded-2xl p-6 border border-white/10 relative help-tooltip-container shadow-sm">
                  <button
                    onClick={() => setHelpTooltip(helpTooltip === 'pageViews' ? null : 'pageViews')}
                    className="absolute top-4 right-4 text-muted-foreground hover:text-foreground transition-colors"
                  >
                    <HelpCircle size={16} />
                  </button>
                  {helpTooltip === 'pageViews' && (
                    <div className="absolute top-10 right-0 z-10 bg-black/80 backdrop-blur border border-white/10 rounded-xl p-3 shadow-lg max-w-xs">
                      <p className="text-xs text-white/70">
                        <strong className="text-white">Visualizações:</strong> Número total de páginas visualizadas pelos visitantes. Uma mesma página pode ser contada várias vezes se for visitada por diferentes pessoas ou na mesma sessão.
                      </p>
                    </div>
                  )}
                  <div className="flex items-center gap-3 mb-3">
                    <div className="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                      <Eye className="text-purple-400" size={20} />
                    </div>
                    <h3 className="text-sm font-semibold text-muted-foreground">Visualizações</h3>
                  </div>
                  <p className="text-3xl font-extrabold text-foreground mb-1">
                    {analytics?.pageViews || 0}
                  </p>
                  <p className="text-sm text-muted-foreground">Média por visita: {(analytics?.pagesPerSession ?? 0).toFixed(1)}</p>
                </div>

                {/* Total Clicks */}
                <div className="bg-card rounded-2xl p-6 border border-white/10 relative help-tooltip-container shadow-sm">
                  <button
                    onClick={() => setHelpTooltip(helpTooltip === 'totalClicks' ? null : 'totalClicks')}
                    className="absolute top-4 right-4 text-muted-foreground hover:text-foreground transition-colors"
                  >
                    <HelpCircle size={16} />
                  </button>
                  {helpTooltip === 'totalClicks' && (
                    <div className="absolute top-10 right-0 z-10 bg-black/80 backdrop-blur border border-white/10 rounded-xl p-3 shadow-lg max-w-xs">
                      <p className="text-xs text-white/70">
                        <strong className="text-white">Total de Cliques:</strong> Número total de cliques registrados em elementos interativos do site, como botões, links e outros elementos clicáveis.
                      </p>
                    </div>
                  )}
                  <div className="flex items-center gap-3 mb-3">
                    <div className="w-10 h-10 rounded-lg bg-green-500/20 flex items-center justify-center">
                      <MousePointerClick className="text-green-400" size={20} />
                    </div>
                    <h3 className="text-sm font-semibold text-muted-foreground">Total de Cliques</h3>
                  </div>
                  <p className="text-3xl font-extrabold text-foreground mb-1">
                    {analytics?.totalClicks || 0}
                  </p>
                  <p className="text-sm text-muted-foreground">Interações registradas</p>
                </div>

                {/* Average Time on Page */}
                <div className="bg-card rounded-2xl p-6 border border-white/10 relative help-tooltip-container shadow-sm">
                  <button
                    onClick={() => setHelpTooltip(helpTooltip === 'avgTime' ? null : 'avgTime')}
                    className="absolute top-4 right-4 text-muted-foreground hover:text-foreground transition-colors"
                  >
                    <HelpCircle size={16} />
                  </button>
                  {helpTooltip === 'avgTime' && (
                    <div className="absolute top-10 right-0 z-10 bg-black/80 backdrop-blur border border-white/10 rounded-xl p-3 shadow-lg max-w-xs">
                      <p className="text-xs text-white/70">
                        <strong className="text-white">Tempo Médio:</strong> Tempo médio que os visitantes permanecem em uma página antes de navegar para outra ou sair do site. Indica o nível de engajamento com o conteúdo.
                      </p>
                    </div>
                  )}
                  <div className="flex items-center gap-3 mb-3">
                    <div className="w-10 h-10 rounded-lg bg-brand/20 flex items-center justify-center">
                      <Clock className="text-brand" size={20} />
                    </div>
                    <h3 className="text-sm font-semibold text-muted-foreground">Tempo Médio</h3>
                  </div>
                  <p className="text-3xl font-extrabold text-foreground mb-1">
                    {formatTime(analytics?.avgTimeOnPage || '0s')}
                  </p>
                  <p className="text-sm text-muted-foreground">Tempo médio na página</p>
                </div>

                {/* Exit Rate */}
                <div className="bg-card rounded-2xl p-6 border border-white/10 relative help-tooltip-container shadow-sm">
                  <button
                    onClick={() => setHelpTooltip(helpTooltip === 'exitRate' ? null : 'exitRate')}
                    className="absolute top-4 right-4 text-muted-foreground hover:text-foreground transition-colors"
                  >
                    <HelpCircle size={16} />
                  </button>
                  {helpTooltip === 'exitRate' && (
                    <div className="absolute top-10 right-0 z-10 bg-black/80 backdrop-blur border border-white/10 rounded-xl p-3 shadow-lg max-w-xs">
                      <p className="text-xs text-white/70">
                        <strong className="text-white">Taxa de Saída:</strong> Porcentagem de sessões que saíram do site após visualizar apenas uma página. Uma taxa alta pode indicar que o conteúdo não está atendendo às expectativas dos visitantes.
                      </p>
                    </div>
                  )}
                  <div className="flex items-center gap-3 mb-3">
                    <div className="w-10 h-10 rounded-lg bg-red-500/20 flex items-center justify-center">
                      <TrendingDownIcon className="text-red-400" size={20} />
                    </div>
                    <h3 className="text-sm font-semibold text-muted-foreground">Taxa de Saída</h3>
                  </div>
                  <p className="text-3xl font-extrabold text-foreground mb-1">
                    {analytics?.bounceRate ? analytics.bounceRate.toFixed(0) : '0'}%
                  </p>
                  <p className="text-sm text-muted-foreground">Sessões com apenas 1 página</p>
                </div>

                {/* Average Session Duration */}
                <div className="bg-card rounded-2xl p-6 border border-white/10 relative help-tooltip-container shadow-sm">
                  <button
                    onClick={() => setHelpTooltip(helpTooltip === 'sessionDuration' ? null : 'sessionDuration')}
                    className="absolute top-4 right-4 text-muted-foreground hover:text-foreground transition-colors"
                  >
                    <HelpCircle size={16} />
                  </button>
                  {helpTooltip === 'sessionDuration' && (
                    <div className="absolute top-10 right-0 z-10 bg-black/80 backdrop-blur border border-white/10 rounded-xl p-3 shadow-lg max-w-xs">
                      <p className="text-xs text-white/70">
                        <strong className="text-white">Duração Média de Sessão:</strong> Tempo médio que os visitantes permanecem no site durante uma sessão. Sessões mais longas geralmente indicam maior interesse e engajamento.
                      </p>
                    </div>
                  )}
                  <div className="flex items-center gap-3 mb-3">
                    <div className="w-10 h-10 rounded-lg bg-indigo-500/20 flex items-center justify-center">
                      <ClockIcon className="text-indigo-400" size={20} />
                    </div>
                    <h3 className="text-sm font-semibold text-muted-foreground">Duração Média de Sessão</h3>
                  </div>
                  <p className="text-3xl font-extrabold text-foreground mb-1">
                    {formatTime(analytics?.avgSessionDuration || '0s')}
                  </p>
                  <p className="text-sm text-muted-foreground">Tempo médio por sessão</p>
                </div>

                {/* Conversion Rate */}
                <div className="bg-card rounded-2xl p-6 border border-white/10 relative help-tooltip-container shadow-sm">
                  <button
                    onClick={() => setHelpTooltip(helpTooltip === 'conversionRate' ? null : 'conversionRate')}
                    className="absolute top-4 right-4 text-muted-foreground hover:text-foreground transition-colors"
                  >
                    <HelpCircle size={16} />
                  </button>
                  {helpTooltip === 'conversionRate' && (
                    <div className="absolute top-10 right-0 z-10 bg-black/80 backdrop-blur border border-white/10 rounded-xl p-3 shadow-lg max-w-xs">
                      <p className="text-xs text-white/70">
                        <strong className="text-white">Taxa de Conversão:</strong> Porcentagem de visitas que resultaram em uma ação desejada (como cliques em elementos importantes). Uma métrica chave para medir a eficácia do site.
                      </p>
                    </div>
                  )}
                  <div className="flex items-center gap-3 mb-3">
                    <div className="w-10 h-10 rounded-lg bg-pink-500/20 flex items-center justify-center">
                      <TrendingUp className="text-pink-400" size={20} />
                    </div>
                    <h3 className="text-sm font-semibold text-muted-foreground">Taxa de Conversão</h3>
                  </div>
                  <p className="text-3xl font-extrabold text-foreground mb-1">
                    {analytics?.conversionRate ? analytics.conversionRate.toFixed(1) : '0'}%
                  </p>
                  <p className="text-sm text-muted-foreground">Cliques por visita</p>
                </div>

                {/* Pages per Session */}
                <div className="bg-card rounded-2xl p-6 border border-white/10 relative help-tooltip-container shadow-sm">
                  <button
                    onClick={() => setHelpTooltip(helpTooltip === 'pagesPerSession' ? null : 'pagesPerSession')}
                    className="absolute top-4 right-4 text-muted-foreground hover:text-foreground transition-colors"
                  >
                    <HelpCircle size={16} />
                  </button>
                  {helpTooltip === 'pagesPerSession' && (
                    <div className="absolute top-10 right-0 z-10 bg-black/80 backdrop-blur border border-white/10 rounded-xl p-3 shadow-lg max-w-xs">
                      <p className="text-xs text-white/70">
                        <strong className="text-white">Páginas por Sessão:</strong> Número médio de páginas visualizadas durante uma sessão. Valores mais altos indicam que os visitantes estão explorando mais o site.
                      </p>
                    </div>
                  )}
                  <div className="flex items-center gap-3 mb-3">
                    <div className="w-10 h-10 rounded-lg bg-cyan-500/20 flex items-center justify-center">
                      <FileText className="text-cyan-400" size={20} />
                    </div>
                    <h3 className="text-sm font-semibold text-muted-foreground">Páginas por Sessão</h3>
                  </div>
                  <p className="text-3xl font-extrabold text-foreground mb-1">
                    {(analytics?.pagesPerSession ?? 0).toFixed(1)}
                  </p>
                  <p className="text-sm text-muted-foreground">Média de páginas visitadas</p>
                </div>
              </div>

              {/* Peak Hours Chart */}
              <div className="bg-card rounded-2xl p-6 border border-white/10 mb-6 shadow-sm">
                <h2 className="text-lg font-extrabold text-foreground mb-4">Horários de Pico</h2>
                {analytics?.peakHours && analytics.peakHours.length > 0 ? (
                  <div className="space-y-4">
                    {/* Primeira linha: 00:00 - 11:00 */}
                    <div className="grid grid-cols-12 gap-2">
                      {analytics.peakHours.slice(0, 12).map((item, index) => {
                        const maxValue = analytics.peakHours?.reduce((max, h) => Math.max(max, h.value), 0) || 1;
                        const barHeight = Math.max((item.value / maxValue) * 100, 5);
                        return (
                          <div key={index} className="flex flex-col items-center">
                            <div className="w-full bg-black/25 rounded-lg p-2 h-24 flex items-end justify-center mb-1 border border-white/10">
                              <div 
                                className="w-full bg-brand rounded-t transition-all duration-300"
                                style={{ 
                                  height: `${barHeight}%`,
                                  minHeight: item.value > 0 ? '4px' : '0px'
                                }}
                              ></div>
                            </div>
                            <p className="text-xs text-muted-foreground mb-0.5">{item.hour}</p>
                            <p className="text-xs text-foreground font-semibold">{item.value}</p>
                          </div>
                        );
                      })}
                    </div>
                    {/* Segunda linha: 12:00 - 23:00 */}
                    <div className="grid grid-cols-12 gap-2">
                      {analytics.peakHours.slice(12, 24).map((item, index) => {
                        const maxValue = analytics.peakHours?.reduce((max, h) => Math.max(max, h.value), 0) || 1;
                        const barHeight = Math.max((item.value / maxValue) * 100, 5);
                        return (
                          <div key={index + 12} className="flex flex-col items-center">
                            <div className="w-full bg-black/25 rounded-lg p-2 h-24 flex items-end justify-center mb-1 border border-white/10">
                              <div 
                                className="w-full bg-brand rounded-t transition-all duration-300"
                                style={{ 
                                  height: `${barHeight}%`,
                                  minHeight: item.value > 0 ? '4px' : '0px'
                                }}
                              ></div>
                            </div>
                            <p className="text-xs text-muted-foreground mb-0.5">{item.hour}</p>
                            <p className="text-xs text-foreground font-semibold">{item.value}</p>
                          </div>
                        );
                      })}
                    </div>
                  </div>
                ) : (
                  <p className="text-sm text-muted-foreground text-center py-4">Nenhum dado disponível</p>
                )}
              </div>

              {/* Activity by Day of Week */}
              <div className="bg-card rounded-2xl p-6 border border-white/10 mb-6 shadow-sm">
                <h2 className="text-lg font-extrabold text-foreground mb-4">Atividade por Dia da Semana</h2>
                {analytics?.activityByDay && analytics.activityByDay.length > 0 ? (
                  <div className="space-y-3">
                    {analytics.activityByDay.map((item, index) => (
                      <div key={index} className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground w-28">{item.day}</span>
                        <div className="flex-1 bg-black/25 border border-white/10 rounded-full h-2 mx-4 overflow-hidden">
                          <div
                            className="bg-brand h-2 rounded-full"
                            style={{ 
                              width: `${(item.value / (analytics.activityByDay?.reduce((max, d) => Math.max(max, d.value), 0) || 1)) * 100}%`
                            }}
                          ></div>
                        </div>
                        <span className="text-sm text-foreground font-semibold w-12 text-right">{item.value}</span>
                      </div>
                    ))}
                  </div>
                ) : (
                  <p className="text-sm text-muted-foreground text-center py-4">Nenhum dado disponível</p>
                )}
              </div>

              {/* Visitors Over Time */}
              <div className="bg-card rounded-2xl p-6 border border-white/10 mb-6 shadow-sm">
                <h2 className="text-lg font-extrabold text-foreground mb-4">Visitantes ao Longo do Tempo</h2>
                {analytics?.visitorsOverTime && analytics.visitorsOverTime.length > 0 ? (
                  <div className="space-y-2">
                    {analytics.visitorsOverTime.map((item, index) => (
                      <div key={index} className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground">{item.date}</span>
                        <div className="flex-1 bg-black/25 border border-white/10 rounded-full h-2 mx-4 overflow-hidden">
                          <div
                            className="bg-brand h-2 rounded-full"
                            style={{ 
                              width: `${(item.value / (analytics.visitorsOverTime?.reduce((max, v) => Math.max(max, v.value), 0) || 1)) * 100}%`
                            }}
                          ></div>
                        </div>
                        <span className="text-sm text-foreground font-semibold w-12 text-right">{item.value}</span>
                      </div>
                    ))}
                  </div>
                ) : (
                  <p className="text-sm text-muted-foreground text-center py-4">Nenhum dado disponível</p>
                )}
              </div>

              {/* Most Visited Pages */}
              <div className="bg-card rounded-2xl p-6 border border-white/10 mb-6 shadow-sm">
                <h2 className="text-lg font-extrabold text-foreground mb-4">Páginas Mais Visitadas</h2>
                {analytics?.topPages && analytics.topPages.length > 0 ? (
                  <div className="space-y-3">
                    {analytics.topPages.slice(0, 5).map((page, index) => (
                      <div key={index} className="flex items-center justify-between">
                        <div className="flex-1">
                          <p className="text-sm text-foreground font-semibold">{page.page}</p>
                          <p className="text-xs text-muted-foreground">
                            {page.views} visualizações • {formatTime(page.avgTime || analytics?.avgTimeOnPage || '0s')} médio
                          </p>
                        </div>
                      </div>
                    ))}
                  </div>
                ) : (
                  <p className="text-sm text-muted-foreground text-center py-4">Nenhum dado disponível</p>
                )}
              </div>

              {/* Devices */}
              <div className="bg-card rounded-2xl p-6 border border-white/10 mb-6 shadow-sm">
                <h2 className="text-lg font-extrabold text-foreground mb-4">Dispositivos</h2>
                {analytics?.devices && analytics.devices.length > 0 ? (
                  <div className="space-y-3">
                    {analytics.devices.map((device, index) => (
                      <div key={index} className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground capitalize">{device.device}</span>
                        <div className="flex items-center gap-3">
                          <span className="text-sm text-foreground font-semibold">{device.sessions}</span>
                          <span className="text-xs text-muted-foreground w-20 text-right">{device.percentage.toFixed(1)}% do total</span>
                        </div>
                      </div>
                    ))}
                  </div>
                ) : (
                  <p className="text-sm text-muted-foreground text-center py-4">Nenhum dado disponível</p>
                )}
              </div>

              {/* Browsers */}
              <div className="bg-card rounded-2xl p-6 border border-white/10 mb-6 shadow-sm">
                <h2 className="text-lg font-extrabold text-foreground mb-4">Navegadores</h2>
                {analytics?.browsers && analytics.browsers.length > 0 ? (
                  <div className="space-y-3">
                    {analytics.browsers.map((browser, index) => (
                      <div key={index} className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground">{browser.browser}</span>
                        <span className="text-sm text-foreground font-semibold">{browser.sessions}</span>
                      </div>
                    ))}
                  </div>
                ) : (
                  <p className="text-sm text-muted-foreground text-center py-4">Nenhum dado disponível</p>
                )}
              </div>

              {/* Operating Systems */}
              <div className="bg-card rounded-2xl p-6 border border-white/10 mb-6 shadow-sm">
                <h2 className="text-lg font-extrabold text-foreground mb-4">Sistemas Operacionais</h2>
                {analytics?.operatingSystems && analytics.operatingSystems.length > 0 ? (
                  <div className="space-y-3">
                    {analytics.operatingSystems.map((os, index) => (
                      <div key={index} className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground">{os.os}</span>
                        <span className="text-sm text-foreground font-semibold">{os.sessions}</span>
                      </div>
                    ))}
                  </div>
                ) : (
                  <p className="text-sm text-muted-foreground text-center py-4">Nenhum dado disponível</p>
                )}
              </div>

              {/* Traffic Sources */}
              <div className="bg-card rounded-2xl p-6 border border-white/10 mb-6 shadow-sm">
                <h2 className="text-lg font-extrabold text-foreground mb-4">Origem do Tráfego</h2>
                {analytics?.trafficSources && analytics.trafficSources.length > 0 ? (
                  <div className="space-y-3">
                    {analytics.trafficSources.map((source, index) => (
                      <div key={index} className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground">{source.source}</span>
                        <span className="text-sm text-foreground font-semibold">{source.sessions}</span>
                      </div>
                    ))}
                  </div>
                ) : (
                  <p className="text-sm text-muted-foreground text-center py-4">Nenhum dado disponível</p>
                )}
              </div>

              {/* Entry Pages */}
              <div className="bg-card rounded-2xl p-6 border border-white/10 mb-6 shadow-sm">
                <h2 className="text-lg font-extrabold text-foreground mb-4">Páginas de Entrada</h2>
                {analytics?.entryPages && analytics.entryPages.length > 0 ? (
                  <div className="space-y-3">
                    {analytics.entryPages.map((page, index) => (
                      <div key={index} className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground">{page.page}</span>
                        <span className="text-sm text-foreground font-semibold">{page.entries} entradas</span>
                      </div>
                    ))}
                  </div>
                ) : (
                  <p className="text-sm text-muted-foreground text-center py-4">Nenhum dado disponível</p>
                )}
              </div>

              {/* Exit Pages */}
              <div className="bg-card rounded-2xl p-6 border border-white/10 mb-6 shadow-sm">
                <h2 className="text-lg font-extrabold text-foreground mb-4">Páginas de Saída</h2>
                {analytics?.exitPages && analytics.exitPages.length > 0 ? (
                  <div className="space-y-3">
                    {analytics.exitPages.map((page, index) => (
                      <div key={index} className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground">{page.page}</span>
                        <span className="text-sm text-foreground font-semibold">{page.exits} saídas</span>
                      </div>
                    ))}
                  </div>
                ) : (
                  <p className="text-sm text-muted-foreground text-center py-4">Nenhum dado disponível</p>
                )}
              </div>

              {/* Countries */}
              <div className="bg-card rounded-2xl p-6 border border-white/10 mb-6 shadow-sm">
                <h2 className="text-lg font-extrabold text-foreground mb-4">Acessos por País</h2>
                {analytics?.countries && analytics.countries.length > 0 ? (
                  <div className="space-y-3">
                    {analytics.countries.map((country, index) => (
                      <div key={index} className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground">{country.country}</span>
                        <div className="flex items-center gap-3">
                          <span className="text-sm text-foreground font-semibold">{country.sessions}</span>
                          <span className="text-xs text-muted-foreground">{country.views} visualizações</span>
                        </div>
                      </div>
                    ))}
                  </div>
                ) : (
                  <p className="text-sm text-muted-foreground text-center py-4">Nenhum dado disponível</p>
                )}
              </div>

              {/* Cities */}
              <div className="bg-card rounded-2xl p-6 border border-white/10 mb-6 shadow-sm">
                <h2 className="text-lg font-extrabold text-foreground mb-4">Acessos por Cidade</h2>
                {analytics?.cities && analytics.cities.length > 0 ? (
                  <div className="space-y-3">
                    {analytics.cities.map((city, index) => (
                      <div key={index} className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground">{city.city}</span>
                        <div className="flex items-center gap-3">
                          <span className="text-sm text-foreground font-semibold">{city.sessions} sessões</span>
                          <span className="text-xs text-muted-foreground">• {city.views} visualizações</span>
                        </div>
                      </div>
                    ))}
                  </div>
                ) : (
                  <p className="text-sm text-muted-foreground text-center py-4">Nenhum dado disponível</p>
                )}
              </div>
            </div>
          )}

          {/* Dashboard Section - Removido */}
          {activeSection === 'dashboard' && (
            <>
              {/* Header */}
              <div className="mb-6 flex items-center justify-between">
                <h1 className="text-2xl font-semibold text-white">Dashboard</h1>
                <button className="flex items-center gap-2 px-4 py-2 bg-[#1A1A1A] text-gray-300 rounded-lg hover:bg-[#2A2A2A] transition-colors border border-[#2A2A2A]">
                  <RefreshCw size={16} />
                  <span className="text-sm">Switch dashboard</span>
                </button>
              </div>

              {/* Filter Tabs */}
              <div className="mb-6 flex flex-wrap gap-2">
                {['All sessions', 'Direct traffic', 'Organic traffic', 'Paid traffic', 'Mobile users', 'Returning users'].map((filter) => (
                  <button
                    key={filter}
                    onClick={() => setActiveFilter(filter)}
                    className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                      activeFilter === filter
                        ? 'bg-[#2A2A2A] text-white border border-[#3A3A3A]'
                        : 'bg-[#1A1A1A] text-gray-400 hover:text-white hover:bg-[#2A2A2A] border border-[#2A2A2A]'
                    }`}
                  >
                    {filter}
                  </button>
                ))}
              </div>

              {/* Key Metrics Cards */}
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                {/* Total Sessions */}
                <div className="bg-[#1A1A1A] rounded-lg p-6 border border-[#2A2A2A]">
                  <div className="flex items-center justify-between mb-4">
                    <h3 className="text-sm font-medium text-gray-400">Total de Sessões</h3>
                  </div>
                  <div>
                    <p className="text-3xl font-semibold text-white mb-1">
                      {analytics?.totalSessions?.toLocaleString('pt-BR') ?? '0'}
                    </p>
                  </div>
                </div>

                {/* Session Duration */}
                <div className="bg-[#1A1A1A] rounded-lg p-6 border border-[#2A2A2A]">
                  <div className="flex items-center justify-between mb-4">
                    <h3 className="text-sm font-medium text-gray-400">Duração da Sessão</h3>
                  </div>
                  <div>
                    <p className="text-3xl font-semibold text-white mb-1">
                      {formatTime(analytics?.avgSessionDuration || '0s')}
                    </p>
                  </div>
                </div>

                {/* Pages per Session */}
                <div className="bg-[#1A1A1A] rounded-lg p-6 border border-[#2A2A2A]">
                  <div className="flex items-center justify-between mb-4">
                    <h3 className="text-sm font-medium text-gray-400">Páginas por Sessão</h3>
                  </div>
                  <div>
                    <p className="text-3xl font-semibold text-white mb-1">
                      {(analytics?.pagesPerSession ?? 0).toFixed(1)}
                    </p>
                  </div>
                </div>
              </div>

          {/* Pages and Screens Table */}
          <div className="bg-[#1A1A1A] rounded-lg border border-[#2A2A2A]">
            <div className="p-6 border-b border-[#2A2A2A]">
              <div className="flex items-center justify-between mb-4">
                <h2 className="text-lg font-semibold text-white">Pages and screens.</h2>
                <div className="flex items-center gap-2">
                  <div className="flex items-center gap-2 bg-[#0F0F0F] rounded-lg p-1 border border-[#2A2A2A]">
                    <button
                      onClick={() => setPageFilter('View all')}
                      className={`px-3 py-1.5 rounded text-sm font-medium transition-colors ${
                        pageFilter === 'View all'
                          ? 'bg-[#2A2A2A] text-white'
                          : 'text-gray-400 hover:text-white'
                      }`}
                    >
                      View all
                    </button>
                    <button
                      onClick={() => setPageFilter('Public')}
                      className={`px-3 py-1.5 rounded text-sm font-medium transition-colors ${
                        pageFilter === 'Public'
                          ? 'bg-[#2A2A2A] text-white'
                          : 'text-gray-400 hover:text-white'
                      }`}
                    >
                      Public
                    </button>
                    <button
                      onClick={() => setPageFilter('Private')}
                      className={`px-3 py-1.5 rounded text-sm font-medium transition-colors ${
                        pageFilter === 'Private'
                          ? 'bg-[#2A2A2A] text-white'
                          : 'text-gray-400 hover:text-white'
                      }`}
                    >
                      Private
                    </button>
                  </div>
                  <div className="relative">
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" size={16} />
                    <input
                      type="text"
                      placeholder="Search"
                      value={searchQuery}
                      onChange={(e) => setSearchQuery(e.target.value)}
                      className="pl-10 pr-4 py-2 bg-[#0F0F0F] border border-[#2A2A2A] rounded-lg text-white text-sm placeholder-gray-500 focus:outline-none focus:border-[#3A3A3A] w-48"
                    />
                  </div>
                </div>
              </div>
            </div>

            {/* Table */}
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="border-b border-[#2A2A2A]">
                    <th className="text-left p-4">
                      <div className="flex items-center gap-2">
                        <input type="checkbox" className="rounded border-[#2A2A2A] bg-[#0F0F0F]" />
                      </div>
                    </th>
                    <th className="text-left p-4">
                      <div className="flex items-center gap-2 text-sm font-medium text-gray-400">
                        Page
                        <Filter size={14} className="cursor-pointer hover:text-white" />
                      </div>
                    </th>
                    <th className="text-left p-4">
                      <div className="flex items-center gap-2 text-sm font-medium text-gray-400">
                        Sessions
                        <Filter size={14} className="cursor-pointer hover:text-white" />
                      </div>
                    </th>
                    <th className="text-left p-4">
                      <div className="flex items-center gap-2 text-sm font-medium text-gray-400">
                        Avg time
                        <Filter size={14} className="cursor-pointer hover:text-white" />
                      </div>
                    </th>
                    <th className="text-left p-4">
                      <div className="flex items-center gap-2 text-sm font-medium text-gray-400">
                        % of total
                        <Filter size={14} className="cursor-pointer hover:text-white" />
                      </div>
                    </th>
                    <th className="p-4"></th>
                  </tr>
                </thead>
                <tbody>
                  {filteredPages.length > 0 ? (
                    filteredPages.map((page, index) => {
                      const percentage = Number(formatPercentage(page.views, analytics?.totalSessions || 1));
                      return (
                        <tr key={index} className="border-b border-[#2A2A2A] hover:bg-[#0F0F0F] transition-colors">
                          <td className="p-4">
                            <input type="checkbox" className="rounded border-[#2A2A2A] bg-[#0F0F0F]" />
                          </td>
                          <td className="p-4">
                            <span className="text-sm text-white font-medium">{page.page}</span>
                          </td>
                          <td className="p-4">
                            <span className="text-sm text-gray-300">{page.views.toLocaleString('pt-BR')}</span>
                          </td>
                          <td className="p-4">
                            <span className="text-sm text-gray-300">{formatTime(page.avgTime || analytics?.avgTimeOnPage || '0s')}</span>
                          </td>
                          <td className="p-4">
                            <div className="flex items-center gap-3">
                              <div className="flex-1 bg-[#0F0F0F] rounded-full h-2 overflow-hidden">
                                <div
                                  className="bg-[#1052E0] h-2 rounded-full"
                                  style={{ width: `${Math.min(percentage, 100)}%` }}
                                />
                              </div>
                              <span className="text-sm text-gray-300 w-16 text-right">{percentage.toFixed(1)}%</span>
                            </div>
                          </td>
                          <td className="p-4">
                            <button className="text-gray-400 hover:text-white transition-colors">
                              <MoreVertical size={16} />
                            </button>
                          </td>
                        </tr>
                      );
                    })
                  ) : (
                    <tr>
                      <td colSpan={6} className="p-8 text-center text-gray-400">
                        No pages found
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
            </>
          )}

          {/* Users Section */}
          {activeSection === 'users' && (
            <div className="space-y-6">
              <h1 className="text-2xl font-semibold text-white">Usuários</h1>
              <div className="bg-[#1A1A1A] rounded-lg p-6 border border-[#2A2A2A]">
                <p className="text-gray-400">Seção de gerenciamento de usuários em breve...</p>
              </div>
            </div>
          )}

          {/* Settings Section */}
          {activeSection === 'settings' && (
            <div className="space-y-6">
              <h1 className="text-2xl font-semibold text-white">Configurações</h1>
              
              {/* Alterar Senhas */}
              <div className="bg-[#1A1A1A] rounded-lg p-6 border border-[#2A2A2A]">
                <h2 className="text-xl font-semibold text-white mb-4 flex items-center gap-2">
                  <Shield size={20} />
                  Alterar Senhas
                </h2>
                
                {passwordError && (
                  <div className="mb-4 p-3 bg-red-500/10 border border-red-500/50 rounded-lg text-red-400 text-sm">
                    {passwordError}
                  </div>
                )}
                
                {passwordSuccess && (
                  <div className="mb-4 p-3 bg-green-500/10 border border-green-500/50 rounded-lg text-green-400 text-sm">
                    {passwordSuccess}
                  </div>
                )}

                <div className="space-y-4">
                  {/* Seleção de Usuário */}
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Selecionar Usuário
                    </label>
                    {loadingUsers ? (
                      <div className="text-gray-400 text-sm">Carregando usuários...</div>
                    ) : (
                      <select
                        value={selectedUserId || ''}
                        onChange={(e) => setSelectedUserId(e.target.value ? parseInt(e.target.value) : null)}
                        className="w-full bg-[#0F0F0F] border border-[#2A2A2A] rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                      >
                        <option value="">Selecione um usuário</option>
                        {users.map((user) => (
                          <option key={user.id} value={user.id}>
                            {user.nome} ({user.email}) {user.isRoot ? ' - Root' : ''}
                          </option>
                        ))}
                      </select>
                    )}
                  </div>

                  {/* Nova Senha */}
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Nova Senha
                    </label>
                    <input
                      type="password"
                      value={newPassword}
                      onChange={(e) => setNewPassword(e.target.value)}
                      placeholder="Digite a nova senha (mínimo 6 caracteres)"
                      className="w-full bg-[#0F0F0F] border border-[#2A2A2A] rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>

                  {/* Confirmar Senha */}
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Confirmar Senha
                    </label>
                    <input
                      type="password"
                      value={confirmPassword}
                      onChange={(e) => setConfirmPassword(e.target.value)}
                      placeholder="Digite a senha novamente"
                      className="w-full bg-[#0F0F0F] border border-[#2A2A2A] rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>

                  {/* Botão Alterar */}
                  <button
                    onClick={handleChangePassword}
                    disabled={!selectedUserId || !newPassword || !confirmPassword || loadingUsers}
                    className="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-600 disabled:cursor-not-allowed text-white font-medium py-2 px-4 rounded-lg transition-colors"
                  >
                    Alterar Senha
                  </button>
                </div>
              </div>
            </div>
          )}
        </div>
      </main>
    </div>
  );
}
