import LogoArianeAndrade from '../../partners/LogoArianeAndrade.png';
import LogoEnxovaisMaciel from '../../partners/LogoEnxovais_Maciel.png';
import LogoGrupoRaca from '../../partners/LogoGrupoRaca.png';
import LogoJatoMinas from '../../partners/LogoJatoMinas.png';
import LogoJM from '../../partners/LogoJM.png';
import LogoTodaArte from '../../partners/logo-todaarte.png';
import LogoRaizes from '../../partners/raizes.png';
import LogoRealDriver from '../../partners/LogoRealDriver.png';

const clients = [
  // Algumas logos são claras e precisam de fundo escuro para boa leitura
  { name: 'Ariane Andrade', logo: LogoArianeAndrade, bgClass: 'bg-black' },
  { name: 'Enxovais Maciel', logo: LogoEnxovaisMaciel, bgClass: 'bg-black' },
  { name: 'Grupo Raça', logo: LogoGrupoRaca, bgClass: 'bg-black', url: 'https://gruporaca.app.br/' }, // preto
  { name: 'Jato Minas', logo: LogoJatoMinas, bgClass: 'bg-white' }, // branco
  { name: 'JM Soluções em Créditos', logo: LogoJM, bgClass: 'bg-white', url: 'https://jmsolucoesmg.com.br/' },
  { name: 'Toda Arte', logo: LogoTodaArte, bgClass: 'bg-black', url: 'https://todaarte.com.br/' }, // fundo preto + link
  { name: 'Raízes', logo: LogoRaizes, bgClass: 'bg-white', url: 'https://raizeseventosltda.com.br/' },
  { name: 'Real Driver', logo: LogoRealDriver, bgClass: 'bg-white' }
];

export default function Clients() {
  return (
    <section id="clientes" className="py-16 md:py-20 px-4 sm:px-6 bg-background animate-fade-up">
      <div className="max-w-7xl mx-auto">
        <div className="mb-10 text-center">
          <h2 className="text-3xl md:text-4xl font-extrabold text-foreground mb-3">
            Clientes e parceiros
          </h2>
          <p className="text-base md:text-lg text-muted-foreground">
            Empresas que confiam em soluções desenvolvidas sob medida
          </p>
        </div>

        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-6 md:gap-6 items-center justify-items-center">
          {clients.map((client) => (
            <div
              key={client.name}
              className="flex items-center justify-center w-full max-w-[150px] opacity-90 hover:opacity-100 transition-opacity hover:-translate-y-1 transition-transform"
            >
              {client.url ? (
                <a
                  href={client.url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="w-full h-full"
                >
                  <div
                    className={`w-full h-full px-4 py-3 h-24 md:h-28 rounded-2xl border border-border bg-card flex items-center justify-center shadow-sm hover:shadow-md transition-shadow ${client.bgClass}`}
                  >
                    <img
                      src={client.logo}
                      alt={client.name}
                      className="max-h-[54px] md:max-h-[58px] w-auto object-contain drop-shadow-[0_1px_1px_rgba(0,0,0,0.18)]"
                    />
                  </div>
                </a>
              ) : (
                <div
                  className={`w-full h-full px-4 py-3 h-24 md:h-28 rounded-2xl border border-border bg-card flex items-center justify-center shadow-sm hover:shadow-md transition-shadow ${client.bgClass}`}
                >
                  <img
                    src={client.logo}
                    alt={client.name}
                    className="max-h-[54px] md:max-h-[58px] w-auto object-contain drop-shadow-[0_1px_1px_rgba(0,0,0,0.18)]"
                  />
                </div>
              )}
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}


