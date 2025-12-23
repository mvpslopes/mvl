import { BookOpen, Users, Award, ArrowRight } from 'lucide-react';

export default function Courses() {
  return (
    <section id="cursos" className="py-20 px-6">
      <div className="max-w-7xl mx-auto">
        <div className="mb-16">
          <h2 className="text-4xl md:text-5xl font-bold text-black mb-4">
            Cursos e área do aluno
          </h2>
          <p className="text-xl text-gray-600">
            Treinamentos práticos e profissionais em ferramentas digitais
          </p>
        </div>

        <div className="grid md:grid-cols-2 gap-12 items-center">
          <div className="space-y-8">
            <div className="flex gap-4">
              <div className="flex-shrink-0">
                <div className="w-12 h-12 bg-[#1052E0] bg-opacity-10 rounded flex items-center justify-center">
                  <BookOpen size={24} className="text-[#1052E0]" />
                </div>
              </div>
              <div>
                <h3 className="text-xl font-bold text-black mb-2">
                  Cursos Personalizados
                </h3>
                <p className="text-gray-600 leading-relaxed">
                  Treinamentos desenvolvidos sob medida para suas necessidades, com foco em Pacote Office e ferramentas profissionais.
                </p>
              </div>
            </div>

            <div className="flex gap-4">
              <div className="flex-shrink-0">
                <div className="w-12 h-12 bg-[#1052E0] bg-opacity-10 rounded flex items-center justify-center">
                  <Users size={24} className="text-[#1052E0]" />
                </div>
              </div>
              <div>
                <h3 className="text-xl font-bold text-black mb-2">
                  Metodologia Prática
                </h3>
                <p className="text-gray-600 leading-relaxed">
                  Ensino direto e objetivo, focado em aplicação real das ferramentas no dia a dia profissional.
                </p>
              </div>
            </div>

            <div className="flex gap-4">
              <div className="flex-shrink-0">
                <div className="w-12 h-12 bg-[#1052E0] bg-opacity-10 rounded flex items-center justify-center">
                  <Award size={24} className="text-[#1052E0]" />
                </div>
              </div>
              <div>
                <h3 className="text-xl font-bold text-black mb-2">
                  Certificação
                </h3>
                <p className="text-gray-600 leading-relaxed">
                  Certificados de conclusão para todos os alunos que completam os treinamentos.
                </p>
              </div>
            </div>
          </div>

          <div className="bg-black text-white p-12 rounded-lg">
            <h3 className="text-3xl font-bold mb-4">
              Área do Aluno
            </h3>
            <p className="text-gray-300 mb-8 leading-relaxed">
              Acesse seus cursos, materiais, aulas gravadas e certificados em um ambiente exclusivo e organizado.
            </p>
            <button className="w-full px-8 py-4 bg-[#1052E0] text-white font-medium rounded hover:bg-[#0d42b8] transition-colors flex items-center justify-center gap-2">
              Acessar área do aluno
              <ArrowRight size={20} />
            </button>
          </div>
        </div>
      </div>
    </section>
  );
}
