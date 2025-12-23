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
    <section id="projetos" className="py-20 px-6 bg-white animate-fade-up">
      <div className="max-w-7xl mx-auto">
        <div className="mb-16">
          <h2 className="text-4xl md:text-5xl font-bold text-black mb-4">
            Projetos e soluções
          </h2>
          <p className="text-xl text-gray-600">
            Alguns dos sistemas e plataformas desenvolvidos
          </p>
        </div>

        <div className="grid md:grid-cols-3 gap-8">
          {projects.map((project, index) => (
            <div
              key={index}
              className="p-8 card-elevated group"
            >
              <h3 className="text-2xl font-bold text-black mb-3">
                {project.title}
              </h3>
              <p className="text-gray-600 leading-relaxed mb-6">
                {project.description}
              </p>
              <div className="flex flex-wrap gap-2 mb-6">
                {project.tags.map((tag, tagIndex) => (
                  <span
                    key={tagIndex}
                    className="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded"
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
