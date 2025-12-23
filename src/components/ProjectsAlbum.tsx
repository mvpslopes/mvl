import ProjetoArianeAndrade from '../../projetos/ariane-andrade.png';
import ProjetoEnxovaisMaciel from '../../projetos/enxovais-maciel.png';
import ProjetoGrupoRaca from '../../projetos/grupo-raca.png';
import ProjetoJmSolucoes from '../../projetos/jm-solucoes.png';
import ProjetoRealDriver from '../../projetos/real-driver.png';
import ProjetoTodaArte from '../../projetos/toda-arte.png';

const album = [
  { title: 'Ariane Andrade', image: ProjetoArianeAndrade },
  { title: 'Enxovais Maciel', image: ProjetoEnxovaisMaciel },
  { title: 'Grupo Raça', image: ProjetoGrupoRaca },
  { title: 'JM Soluções', image: ProjetoJmSolucoes },
  { title: 'Real Driver', image: ProjetoRealDriver },
  { title: 'Toda Arte', image: ProjetoTodaArte }
];

export default function ProjectsAlbum() {
  return (
    <section className="py-20 px-6 bg-black animate-fade-up">
      <div className="max-w-7xl mx-auto">
        <div className="mb-6">
          <h2 className="text-3xl md:text-4xl font-bold text-white">
            Álbum de projetos
          </h2>
          <p className="text-sm md:text-base text-gray-400 mt-2 max-w-2xl">
            Alguns layouts e sistemas desenvolvidos recentemente para diferentes negócios e segmentos.
          </p>
        </div>

        <div className="grid grid-cols-2 md:grid-cols-3 gap-4 md:gap-6">
          {album.map((item) => (
            <div
              key={item.title}
              className="overflow-hidden rounded-xl border border-gray-800 bg-white/5 hover:bg-white/10 transition-transform transition-colors duration-200 hover:-translate-y-1 hover:shadow-xl"
            >
              <div className="aspect-[16/9] overflow-hidden bg-black rounded-t-xl">
                <img
                  src={item.image}
                  alt={item.title}
                  className="w-full h-full object-cover object-left-top"
                />
              </div>
              <div className="px-3 py-2">
                <p className="text-xs font-medium text-gray-200 truncate">
                  {item.title}
                </p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}


