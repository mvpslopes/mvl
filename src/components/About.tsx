export default function About() {
  return (
    <section className="py-16 md:py-20 px-4 sm:px-6 bg-background animate-fade-up">
      <div className="max-w-7xl mx-auto">
        <div className="max-w-3xl">
          <div className="flex items-center gap-3 mb-6">
            <div className="h-10 w-1 rounded-full bg-brand" />
            <h2 className="text-4xl md:text-5xl font-extrabold text-foreground">
              Sobre
            </h2>
          </div>
          <div className="space-y-4 text-lg text-muted-foreground leading-relaxed">
            <p>
              Profissional especializado em tecnologia, sistemas e educação digital, com foco em desenvolver soluções
              práticas e escaláveis para empresas e pessoas.
            </p>
            <p>
              Do desenvolvimento de plataformas personalizadas ao ensino de ferramentas digitais essenciais, meu
              trabalho é transformar desafios em resultados concretos através da tecnologia.
            </p>
            <p>
              Com experiência em consultoria, suporte técnico e criação de sistemas sob medida, entrego projetos que
              fazem a diferença no dia a dia de quem usa.
            </p>
          </div>
        </div>
      </div>
    </section>
  );
}
