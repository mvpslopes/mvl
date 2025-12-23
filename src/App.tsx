import { useEffect, useState } from 'react';
import Header from './components/Header';
import Hero from './components/Hero';
import About from './components/About';
import Services from './components/Services';
import Clients from './components/Clients';
import Projects from './components/Projects';
import ProjectsAlbum from './components/ProjectsAlbum';
import Contact from './components/Contact';
import Footer from './components/Footer';
import WhatsAppFloatingButton from './components/WhatsAppFloatingButton';
import SplashScreen from './components/SplashScreen';

function App() {
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const timeout = setTimeout(() => {
      setIsLoading(false);
    }, 2500);

    return () => clearTimeout(timeout);
  }, []);

  return (
    <div className="min-h-screen bg-white">
      {isLoading && <SplashScreen />}
      <Header />
      <Hero />
      <About />
      <Services />
      <Clients />
      <Projects />
      <ProjectsAlbum />
      <Contact />
      <Footer />
      <WhatsAppFloatingButton />
    </div>
  );
}

export default App;
