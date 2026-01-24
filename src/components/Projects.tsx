export default function Projects() {
  const projects = [
    {
      title: 'Landing Pages',
      description:
        'Páginas focadas em conversão para campanhas, lançamentos, captura de leads e apresentação de serviços de forma objetiva.',
      tags: ['Conversão', 'Campanhas', 'Leads']
    },
    {
      title: 'Sistemas Web Personalizados',
      description:
        'Aplicações web desenvolvidas sob medida para os processos do seu negócio, com foco em performance, segurança e escalabilidade.',
      tags: ['Sistema Web', 'Dashboard', 'Processos']
    },
    {
      title: 'Integrações e Automação de Processos',
      description:
        'Integração entre sistemas, automação de rotinas manuais e uso de APIs para conectar ferramentas e ganhar eficiência.',
      tags: ['APIs', 'Automação', 'Integrações']
    }
  ];

  return (
    <section id="projetos" className="py-16 md:py-20 px-4 sm:px-6 bg-background animate-fade-up">
      <div className="max-w-7xl mx-auto">
        <div className="mb-16">
          <h2 className="text-4xl md:text-5xl font-extrabold text-foreground mb-4">
            Projetos e soluções
          </h2>
          <p className="text-xl text-muted-foreground">
            Alguns dos sistemas e plataformas desenvolvidos
          </p>
        </div>

        <div className="grid gap-6 md:grid-cols-3 md:gap-8">
          {projects.map((project, index) => (
            <div
              key={index}
              className="p-8 card-elevated group"
            >
              <h3 className="text-2xl font-bold text-foreground mb-3">
                {project.title}
              </h3>
              <p className="text-muted-foreground leading-relaxed mb-6">
                {project.description}
              </p>
              <div className="flex flex-wrap gap-2 mb-6">
                {project.tags.map((tag, tagIndex) => (
                  <span
                    key={tagIndex}
                    className="px-3 py-1 text-sm bg-muted text-muted-foreground rounded"
                  >
                    {tag}
                  </span>
                ))}
              </div>
              {/* Espaço reservado para futuros links/detalhes específicos */}
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
