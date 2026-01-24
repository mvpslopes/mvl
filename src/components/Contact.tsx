import { Mail, Send } from 'lucide-react';
import { FaWhatsapp } from 'react-icons/fa';
import { useState } from 'react';

export default function Contact() {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    message: ''
  });

  const [isSubmitting, setIsSubmitting] = useState(false);
  const [feedback, setFeedback] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (isSubmitting) return;

    setIsSubmitting(true);
    setFeedback(null);

    try {
      const response = await fetch('/send-contact.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
      });

      // Verificar se a resposta é JSON válido
      const contentType = response.headers.get('content-type');
      if (!contentType || !contentType.includes('application/json')) {
        // Se não for JSON, provavelmente é um erro 404 ou HTML de erro
        if (response.status === 404) {
          throw new Error('Serviço de envio não encontrado. Por favor, entre em contato diretamente pelo e-mail ou WhatsApp.');
        }
        await response.text();
        throw new Error('Resposta inválida do servidor. Tente novamente mais tarde.');
      }

      const data = await response.json();

      if (!response.ok || !data.ok) {
        throw new Error(data?.message || 'Não foi possível enviar a mensagem.');
      }

      setFeedback({ type: 'success', message: 'Mensagem enviada com sucesso! Em breve entrarei em contato.' });
      setFormData({ name: '', email: '', message: '' });
    } catch (error) {
      console.error(error);
      setFeedback({
        type: 'error',
        message: 'Ocorreu um erro ao enviar sua mensagem. Tente novamente em alguns instantes.'
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
  };

  return (
    <section id="contato" className="py-16 md:py-20 px-4 sm:px-6 bg-background animate-fade-up">
      <div className="max-w-7xl mx-auto">
        <div className="mb-16 text-center">
          <h2 className="text-4xl md:text-5xl font-extrabold text-foreground mb-4">
            Contato
          </h2>
          <p className="text-xl text-muted-foreground">
            Entre em contato para solicitar uma proposta ou tirar dúvidas
          </p>
        </div>

        <div className="grid gap-10 md:grid-cols-2 md:gap-12">
          <div className="rounded-2xl border border-border bg-card p-8 shadow-sm">
            <h3 className="text-2xl font-extrabold text-foreground mb-6">
              Fale comigo
            </h3>

            <div className="space-y-6">
              <div className="flex items-start gap-4">
                <div className="flex-shrink-0">
                  <div className="w-12 h-12 bg-brand/10 rounded-lg flex items-center justify-center">
                    <Mail size={22} className="text-brand" />
                  </div>
                </div>
                <div>
                  <h4 className="font-bold text-foreground mb-1">E-mail</h4>
                  <a href="mailto:contato@mvlopes.com.br" className="text-muted-foreground hover:text-brand transition-colors">
                    contato@mvlopes.com.br
                  </a>
                </div>
              </div>

              <div className="flex items-start gap-4">
                <div className="flex-shrink-0">
                  <div className="w-12 h-12 bg-brand/10 rounded-lg flex items-center justify-center">
                    <FaWhatsapp className="text-[#25D366]" size={26} />
                  </div>
                </div>
                <div>
                  <h4 className="font-bold text-foreground mb-1">WhatsApp</h4>
                  <a
                    href="https://wa.me/5531982304737?text=Ol%C3%A1%2C%20vim%20pelo%20site%20da%20MVL%2C%20gostaria%20de%20mais%20informa%C3%A7%C3%B5es%20sobre%20servi%C3%A7os."
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-muted-foreground hover:text-brand transition-colors"
                  >
                    (31) 98230-4737
                  </a>
                </div>
              </div>
            </div>

            <div className="mt-8">
              <a
                href="https://wa.me/5531982304737?text=Ol%C3%A1%2C%20vim%20pelo%20site%20da%20MVL%2C%20gostaria%20de%20mais%20informa%C3%A7%C3%B5es%20sobre%20servi%C3%A7os."
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-2 px-6 py-3 bg-[#25D366] text-white font-semibold rounded-lg hover:bg-[#20ba5a] transition-colors"
              >
                <FaWhatsapp size={20} />
                Chamar no WhatsApp
              </a>
            </div>
          </div>

          <div className="rounded-2xl border border-border bg-card p-8 shadow-sm">
            <form onSubmit={handleSubmit} className="space-y-6">
              <div>
                <label htmlFor="name" className="block text-sm font-semibold text-foreground mb-2">
                  Nome
                </label>
                <input
                  type="text"
                  id="name"
                  name="name"
                  value={formData.name}
                  onChange={handleChange}
                  required
                  className="w-full px-4 py-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 focus:ring-offset-background transition-shadow"
                  placeholder="Seu nome completo"
                />
              </div>

              <div>
                <label htmlFor="email" className="block text-sm font-semibold text-foreground mb-2">
                  E-mail
                </label>
                <input
                  type="email"
                  id="email"
                  name="email"
                  value={formData.email}
                  onChange={handleChange}
                  required
                  className="w-full px-4 py-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 focus:ring-offset-background transition-shadow"
                  placeholder="seu@email.com"
                />
              </div>

              <div>
                <label htmlFor="message" className="block text-sm font-semibold text-foreground mb-2">
                  Mensagem
                </label>
                <textarea
                  id="message"
                  name="message"
                  value={formData.message}
                  onChange={handleChange}
                  required
                  rows={5}
                  className="w-full px-4 py-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 focus:ring-offset-background transition-shadow resize-none"
                  placeholder="Como posso ajudar você?"
                />
              </div>

              <button
                type="submit"
                disabled={isSubmitting}
                className="w-full btn-brand py-4 disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                {isSubmitting ? 'Enviando...' : 'Enviar mensagem'}
                <Send size={20} />
              </button>
            </form>

            {feedback && (
              <p
                className={`mt-4 text-sm ${
                  feedback.type === 'success' ? 'text-green-600' : 'text-red-600'
                }`}
              >
                {feedback.message}
              </p>
            )}
          </div>
        </div>
      </div>
    </section>
  );
}
