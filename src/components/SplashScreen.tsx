import logoMvlBranco from '../../logo/logo_mvl-2_branco.png';

export default function SplashScreen() {
  return (
    <div className="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-black text-white splash-fade-out">
      <div className="flex flex-col items-center gap-4">
        <img
          src={logoMvlBranco}
          alt="Marcus Lopes - Soluções Digitais"
          className="h-10 w-auto"
        />
        <div className="mt-2 h-8 w-8 rounded-full border-2 border-gray-600 border-t-[#1052E0] animate-spin" />
      </div>
    </div>
  );
}


