import { ExternalLink } from 'lucide-react';

export default function Projects() {
  const projects = [
    {
      title: 'Sistema de Gestão Escolar',
      description: 'Plataforma completa para gerenciamento de cursos, alunos, notas e conteúdos educacionais.',
      tags: ['Educação', 'Sistema Web', 'Dashboard']
    },
    {
      title: 'Portal Corporativo',
      description: 'Sistema integrado para gestão interna de documentos, processos e comunicação empresarial.',
      tags: ['Empresarial', 'Automação', 'Integração']
    },
    {
      title: 'Plataforma de Cursos Online',
      description: 'Ambiente digital para hospedagem e distribuição de cursos com área do aluno e certificados.',
      tags: ['EAD', 'E-learning', 'Certificação']
    }
  ];

  return (
    <section id="projetos" className="py-20 px-6 bg-gray-50">
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
              className="p-8 bg-white border border-gray-200 rounded-lg hover:border-[#1052E0] transition-all group"
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
              <button className="flex items-center gap-2 text-[#1052E0] font-medium group-hover:gap-3 transition-all">
                Ver detalhes
                <ExternalLink size={16} />
              </button>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
