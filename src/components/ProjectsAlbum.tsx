import ProjetoArianeAndrade from '../../projetos/ariane-andrade.png';
import ProjetoEnxovaisMaciel from '../../projetos/enxovais-maciel.png';
import ProjetoGrupoRaca from '../../projetos/grupo-raca.png';
import ProjetoJmSolucoes from '../../projetos/jm-solucoes.png';
import ProjetoTodaArte from '../../projetos/toda-arte.png';
import ProjetoRealDriver from '../../projetos/real-driver.png';

const album = [
  { title: 'Ariane Andrade', image: ProjetoArianeAndrade },
  { title: 'Enxovais Maciel', image: ProjetoEnxovaisMaciel },
  { title: 'Grupo Raça', image: ProjetoGrupoRaca },
  { title: 'JM Soluções', image: ProjetoJmSolucoes },
  { title: 'Toda Arte', image: ProjetoTodaArte },
  { title: 'Real Driver', image: ProjetoRealDriver }
];

export default function ProjectsAlbum() {
  return (
    <section className="py-16 md:py-20 px-4 sm:px-6 bg-muted/40 animate-fade-up">
      <div className="max-w-7xl mx-auto">
        <div className="mb-6">
          <h2 className="text-3xl md:text-4xl font-extrabold text-foreground">
            Álbum de projetos
          </h2>
          <p className="text-sm md:text-base text-muted-foreground mt-2 max-w-2xl">
            Alguns layouts e sistemas desenvolvidos recentemente para diferentes negócios e segmentos.
          </p>
        </div>

        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:gap-6">
          {album.map((item) => (
            <div
              key={item.title}
              className="overflow-hidden rounded-2xl border border-border bg-card hover:border-brand/40 transition-transform transition-colors duration-200 hover:-translate-y-1 hover:shadow-xl"
            >
              <div className="aspect-[16/9] overflow-hidden bg-black/5 rounded-t-2xl">
                <img
                  src={item.image}
                  alt={item.title}
                  className="w-full h-full object-cover object-left-top"
                />
              </div>
              <div className="px-4 py-3">
                <p className="text-sm font-semibold text-foreground truncate">
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


