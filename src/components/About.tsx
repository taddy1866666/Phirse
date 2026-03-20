import { HiUser } from "react-icons/hi";

export default function About() {
  return (
    <section id="about">
      <div className="bg-card-bg border border-card-border rounded-2xl p-6 sm:p-8">
        <h2 className="text-xl font-bold flex items-center gap-2 mb-4">
          <HiUser className="text-muted" size={20} />
          About
        </h2>

        <div className="space-y-4 text-muted text-sm leading-relaxed">
          <p>
            I am a Full Stack Developer with experience in software development
            and IT technical support. I have hands-on experience in PC setup,
            operating system installation, network troubleshooting, printer
            configuration, and IT equipment management. I have also worked on
            building and assembling PCs, installing software and drivers, and
            resolving hardware and connectivity issues across different
            departments. I focus on building simple, reliable, and practical web
            applications while continuously improving my technical skills. These
            experiences strengthened my problem-solving and troubleshooting
            abilities, allowing me to deliver efficient technology solutions.
          </p>
        </div>
      </div>
    </section>
  );
}