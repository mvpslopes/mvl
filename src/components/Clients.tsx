import { useEffect, useState } from 'react';
import LogoArianeAndrade from '../../partners/LogoArianeAndrade.png';
import LogoEnxovaisMaciel from '../../partners/LogoEnxovais_Maciel.png';
import LogoGrupoRaca from '../../partners/LogoGrupoRaca.png';
import LogoJatoMinas from '../../partners/LogoJatoMinas.png';
import LogoJM from '../../partners/LogoJM.png';
import LogoTodaArte from '../../partners/logo-todaarte.png';
import LogoRaizes from '../../partners/raizes.png';
import LogoRealDriver from '../../partners/LogoRealDriver.png';
import { fetchClientesPublic } from '../lib/clientesApi';

type ClientItem = {
  id?: string;
  name: string;
  logo: string;
  bgColor: string;
  url?: string;
};

const fallbackClients: ClientItem[] = [
  { name: 'Ariane Andrade', logo: LogoArianeAndrade, bgColor: '#000000' },
  { name: 'Enxovais Maciel', logo: LogoEnxovaisMaciel, bgColor: '#000000' },
  { name: 'Grupo Raça', logo: LogoGrupoRaca, bgColor: '#000000', url: 'https://gruporaca.app.br/' },
  { name: 'Jato Minas', logo: LogoJatoMinas, bgColor: '#FFFFFF' },
  { name: 'JM Soluções em Créditos', logo: LogoJM, bgColor: '#FFFFFF', url: 'https://jmsolucoesmg.com.br/' },
  { name: 'Toda Arte', logo: LogoTodaArte, bgColor: '#000000', url: 'https://todaarte.com.br/' },
  { name: 'Raízes', logo: LogoRaizes, bgColor: '#FFFFFF', url: 'https://raizeseventosltda.com.br/' },
  { name: 'Real Driver', logo: LogoRealDriver, bgColor: '#FFFFFF' },
];

function ClientCard({ client }: { client: ClientItem }) {
  const inner = (
    <div
      className="w-full h-full px-4 py-3 h-24 md:h-28 rounded-2xl border border-border bg-card flex items-center justify-center shadow-sm hover:shadow-md transition-shadow"
      style={{ backgroundColor: client.bgColor }}
    >
      <img
        src={client.logo}
        alt={client.name}
        className="max-h-[54px] md:max-h-[58px] w-auto object-contain drop-shadow-[0_1px_1px_rgba(0,0,0,0.18)]"
      />
    </div>
  );

  if (client.url) {
    return (
      <a href={client.url} target="_blank" rel="noopener noreferrer" className="w-full h-full">
        {inner}
      </a>
    );
  }
  return inner;
}

export default function Clients() {
  const [clients, setClients] = useState<ClientItem[]>(fallbackClients);

  useEffect(() => {
    let cancelled = false;
    fetchClientesPublic()
      .then((list) => {
        if (cancelled || list.length === 0) return;
        setClients(
          list
            .filter((c) => c.logo_url)
            .map((c) => ({
              id: c.id,
              name: c.name,
              logo: c.logo_url as string,
              bgColor: c.bg_color || '#FFFFFF',
              url: c.url || undefined,
            }))
        );
      })
      .catch(() => {
        /* mantém fallback */
      });
    return () => {
      cancelled = true;
    };
  }, []);

  return (
    <section id="clientes" className="py-16 md:py-20 px-4 sm:px-6 bg-background animate-fade-up">
      <div className="max-w-7xl mx-auto">
        <div className="mb-10 text-center">
          <h2 className="text-3xl md:text-4xl font-extrabold text-foreground mb-3">Clientes e parceiros</h2>
          <p className="text-base md:text-lg text-muted-foreground">
            Empresas que confiam em soluções desenvolvidas sob medida
          </p>
        </div>

        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-6 md:gap-6 items-center justify-items-center">
          {clients.map((client) => (
            <div
              key={client.id ?? client.name}
              className="flex items-center justify-center w-full max-w-[150px] opacity-90 hover:opacity-100 transition-opacity hover:-translate-y-1 transition-transform"
            >
              <ClientCard client={client} />
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
