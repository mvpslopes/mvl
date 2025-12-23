import { Mail, Send } from 'lucide-react';
import { FaWhatsapp } from 'react-icons/fa';
import { useState } from 'react';

export default function Contact() {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    message: ''
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    console.log('Form submitted:', formData);
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
  };

  return (
    <section id="contato" className="py-20 px-6 bg-gray-50 animate-fade-up">
      <div className="max-w-7xl mx-auto">
        <div className="mb-16 text-center">
          <h2 className="text-4xl md:text-5xl font-bold text-black mb-4">
            Contato
          </h2>
          <p className="text-xl text-gray-600">
            Entre em contato para solicitar uma proposta ou tirar dúvidas
          </p>
        </div>

        <div className="grid md:grid-cols-2 gap-12">
          <div>
            <h3 className="text-2xl font-bold text-black mb-6">
              Fale comigo
            </h3>

            <div className="space-y-6">
              <div className="flex items-start gap-4">
                <div className="flex-shrink-0">
                  <div className="w-12 h-12 bg-[#1052E0] bg-opacity-10 rounded flex items-center justify-center">
                    <Mail size={24} className="text-[#1052E0]" />
                  </div>
                </div>
                <div>
                  <h4 className="font-bold text-black mb-1">E-mail</h4>
                  <a href="mailto:contato@mvlopes.com.br" className="text-gray-600 hover:text-[#1052E0] transition-colors">
                    contato@mvlopes.com.br
                  </a>
                </div>
              </div>

              <div className="flex items-start gap-4">
                <div className="flex-shrink-0">
                  <div className="w-12 h-12 bg-[#1052E0] bg-opacity-10 rounded flex items-center justify-center">
                    <FaWhatsapp className="text-[#25D366]" size={26} />
                  </div>
                </div>
                <div>
                  <h4 className="font-bold text-black mb-1">WhatsApp</h4>
                  <a
                    href="https://wa.me/5531982304737?text=Ol%C3%A1%2C%20vim%20pelo%20site%20da%20MVL%2C%20gostaria%20de%20mais%20informa%C3%A7%C3%B5es%20sobre%20servi%C3%A7os."
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-gray-600 hover:text-[#1052E0] transition-colors"
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
                className="inline-flex items-center gap-2 px-6 py-3 bg-[#25D366] text-white font-medium rounded hover:bg-[#20ba5a] transition-colors"
              >
                <FaWhatsapp size={20} />
                Chamar no WhatsApp
              </a>
            </div>
          </div>

          <div>
            <form onSubmit={handleSubmit} className="space-y-6">
              <div>
                <label htmlFor="name" className="block text-sm font-medium text-gray-900 mb-2">
                  Nome
                </label>
                <input
                  type="text"
                  id="name"
                  name="name"
                  value={formData.name}
                  onChange={handleChange}
                  required
                  className="w-full px-4 py-3 border border-gray-300 rounded focus:outline-none focus:border-[#1052E0] transition-colors"
                  placeholder="Seu nome completo"
                />
              </div>

              <div>
                <label htmlFor="email" className="block text-sm font-medium text-gray-900 mb-2">
                  E-mail
                </label>
                <input
                  type="email"
                  id="email"
                  name="email"
                  value={formData.email}
                  onChange={handleChange}
                  required
                  className="w-full px-4 py-3 border border-gray-300 rounded focus:outline-none focus:border-[#1052E0] transition-colors"
                  placeholder="seu@email.com"
                />
              </div>

              <div>
                <label htmlFor="message" className="block text-sm font-medium text-gray-900 mb-2">
                  Mensagem
                </label>
                <textarea
                  id="message"
                  name="message"
                  value={formData.message}
                  onChange={handleChange}
                  required
                  rows={5}
                  className="w-full px-4 py-3 border border-gray-300 rounded focus:outline-none focus:border-[#1052E0] transition-colors resize-none"
                  placeholder="Como posso ajudar você?"
                />
              </div>

              <button
                type="submit"
                className="w-full px-8 py-4 bg-black text-white font-medium rounded hover:bg-gray-900 transition-colors flex items-center justify-center gap-2"
              >
                Enviar mensagem
                <Send size={20} />
              </button>
            </form>
          </div>
        </div>
      </div>
    </section>
  );
}
