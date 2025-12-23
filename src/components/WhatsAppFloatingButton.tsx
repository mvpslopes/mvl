import { FaWhatsapp } from 'react-icons/fa';

export default function WhatsAppFloatingButton() {
  const whatsappLink =
    'https://wa.me/5531982304737?text=Ol%C3%A1%2C%20vim%20pelo%20site%20da%20MVL%2C%20gostaria%20de%20mais%20informa%C3%A7%C3%B5es%20sobre%20servi%C3%A7os.';

  return (
    <a
      href={whatsappLink}
      target="_blank"
      rel="noopener noreferrer"
      className="fixed bottom-6 right-6 z-50 inline-flex items-center justify-center w-14 h-14 rounded-full bg-[#25D366] text-white shadow-lg hover:bg-[#20ba5a] transition-colors animate-pulse-soft"
      aria-label="Fale conosco pelo WhatsApp"
    >
      <FaWhatsapp size={28} />
    </a>
  );
}


