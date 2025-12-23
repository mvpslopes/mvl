import LogoArianeAndrade from '../../partners/LogoArianeAndrade.png';
import LogoEnxovaisMaciel from '../../partners/LogoEnxovais_Maciel.png';
import LogoGrupoRaca from '../../partners/LogoGrupoRaca.png';
import LogoJatoMinas from '../../partners/LogoJatoMinas.png';
import LogoJM from '../../partners/LogoJM.png';
import LogoRealDriver from '../../partners/LogoRealDriver.png';
import LogoTodaArte from '../../partners/logo-todaarte.png';
import LogoRaizes from '../../partners/raizes.png';

const clients = [
  { name: 'Ariane Andrade', logo: LogoArianeAndrade, bgClass: 'bg-gray-100' }, // cinza mais claro
  { name: 'Enxovais Maciel', logo: LogoEnxovaisMaciel, bgClass: 'bg-emerald-700' }, // verde mais escuro
  { name: 'Grupo Raça', logo: LogoGrupoRaca, bgClass: 'bg-black', url: 'https://gruporaca.app.br/' }, // preto
  { name: 'Jato Minas', logo: LogoJatoMinas, bgClass: 'bg-white' }, // branco
  { name: 'JM Soluções em Créditos', logo: LogoJM, bgClass: 'bg-blue-800', url: 'https://jmsolucoesmg.com.br/' }, // azul mais escuro
  { name: 'Real Driver', logo: LogoRealDriver, bgClass: 'bg-gray-300' }, // cinza mais claro
  { name: 'Toda Arte', logo: LogoTodaArte, bgClass: 'bg-black', url: 'https://todaarte.com.br/' }, // fundo preto + link
  { name: 'Raízes', logo: LogoRaizes, bgClass: 'bg-gray-100', url: 'https://raizeseventosltda.com.br/' } // cinza claro + link
];

export default function Clients() {
  return (
    <section id="clientes" className="py-16 px-6 bg-black animate-fade-up">
      <div className="max-w-7xl mx-auto">
        <div className="mb-10 text-center">
          <h2 className="text-3xl md:text-4xl font-bold text-white mb-3">
            Clientes e parceiros
          </h2>
          <p className="text-base md:text-lg text-gray-300">
            Empresas que confiam em soluções desenvolvidas sob medida
          </p>
        </div>

        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-6 items-center justify-items-center">
          {clients.map((client) => (
            <div
              key={client.name}
              className="flex items-center justify-center w-full max-w-[140px] opacity-80 hover:opacity-100 transition-opacity hover:-translate-y-1 transition-transform"
            >
              {client.url ? (
                <a
                  href={client.url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="w-full h-full"
                >
                  <div
                    className={`w-full h-full px-4 py-3 h-24 md:h-28 rounded-lg border border-zinc-800 flex items-center justify-center ${client.bgClass}`}
                  >
                    <img
                      src={client.logo}
                      alt={client.name}
                      className="max-h-full w-auto object-contain"
                    />
                  </div>
                </a>
              ) : (
                <div
                  className={`w-full h-full px-4 py-3 h-24 md:h-28 rounded-lg border border-zinc-800 flex items-center justify-center ${client.bgClass}`}
                >
                  <img
                    src={client.logo}
                    alt={client.name}
                    className="max-h-full w-auto object-contain"
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


