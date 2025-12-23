export default function Footer() {
  const currentYear = new Date().getFullYear();

  return (
    <footer className="py-12 px-6 bg-black text-white">
      <div className="max-w-7xl mx-auto">
        <div className="flex flex-col md:flex-row items-center justify-between gap-6">
          <div className="flex items-center gap-2">
            <div className="text-2xl font-bold tracking-tight">
              MVL<span className="text-[#1052E0]">.</span>
            </div>
          </div>

          <div className="text-center md:text-right">
            <p className="text-gray-400 text-sm">
              © {currentYear} MVLopes. Todos os direitos reservados.
            </p>
          </div>
        </div>
      </div>
    </footer>
  );
}
