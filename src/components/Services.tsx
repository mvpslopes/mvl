import { Code, GraduationCap, Wrench, Headphones } from 'lucide-react';

export default function Services() {
  const services = [
    {
      icon: Code,
      title: 'Sistemas e plataformas digitais',
      description: 'Desenvolvimento de sistemas personalizados, plataformas web e soluções tecnológicas escaláveis para seu negócio.'
    },
    {
      icon: GraduationCap,
      title: 'Cursos personalizados',
      description: 'Treinamentos práticos e direcionados em Pacote Office e ferramentas digitais essenciais para profissionais.'
    },
    {
      icon: Wrench,
      title: 'Soluções tecnológicas sob medida',
      description: 'Desenvolvimento de ferramentas e sistemas específicos para atender necessidades únicas do seu projeto.'
    },
    {
      icon: Headphones,
      title: 'Consultoria e suporte técnico',
      description: 'Assessoria especializada em tecnologia, infraestrutura digital e resolução de problemas técnicos complexos.'
    }
  ];

  return (
    <section id="servicos" className="py-20 px-6">
      <div className="max-w-7xl mx-auto">
        <div className="mb-16">
          <h2 className="text-4xl md:text-5xl font-bold text-black mb-4">
            O que faço
          </h2>
          <p className="text-xl text-gray-600">
            Serviços desenvolvidos para resolver desafios reais com tecnologia
          </p>
        </div>

        <div className="grid md:grid-cols-2 gap-8">
          {services.map((service, index) => (
            <div
              key={index}
              className="p-8 bg-white border border-gray-200 rounded-lg hover:border-[#1052E0] transition-colors"
            >
              <service.icon size={32} className="text-[#1052E0] mb-4" />
              <h3 className="text-2xl font-bold text-black mb-3">
                {service.title}
              </h3>
              <p className="text-gray-600 leading-relaxed">
                {service.description}
              </p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
