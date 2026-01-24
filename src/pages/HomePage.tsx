import { useEffect, useState } from 'react';
import About from '../components/About';
import Clients from '../components/Clients';
import Contact from '../components/Contact';
import Footer from '../components/Footer';
import Header from '../components/Header';
import Hero from '../components/Hero';
import Projects from '../components/Projects';
import ProjectsAlbum from '../components/ProjectsAlbum';
import Services from '../components/Services';
import SplashScreen from '../components/SplashScreen';
import WhatsAppFloatingButton from '../components/WhatsAppFloatingButton';

export default function HomePage() {
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const timeout = setTimeout(() => {
      setIsLoading(false);
    }, 1200);

    return () => clearTimeout(timeout);
  }, []);

  return (
    <div className="min-h-screen bg-background text-foreground">
      {isLoading && <SplashScreen />}
      <Header />
      <main>
        <Hero />
        <About />
        <Services />
        <Clients />
        <Projects />
        <ProjectsAlbum />
        <Contact />
      </main>
      <Footer />
      <WhatsAppFloatingButton />
    </div>
  );
}

