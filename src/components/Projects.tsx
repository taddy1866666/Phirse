import { HiFolder } from "react-icons/hi";

const projects = [
  {
    title: "Phirse – Capstone Project",
    tags: ["Native PHP"],
    image: "/projects/chadgpt.png",
    description:
      "web-based student-centered reservation platform for student organizations at Pamantasan ng Lungsod ng Valenzuela. ",
    href: "#",
  },
];

export default function Projects() {
  return (
    <section id="projects">
      <div className="bg-card-bg border border-card-border rounded-2xl p-6 sm:p-8">
        <h2 className="text-xl font-bold flex items-center gap-2 mb-6">
          <HiFolder className="text-muted" size={20} />
          Projects
        </h2>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {projects.map((project) => (
            <a
              key={project.title}
              href={project.href}
              className="group block rounded-xl overflow-hidden border border-card-border hover:border-accent transition-colors bg-background"
            >
              <div className="aspect-video bg-tag-bg overflow-hidden">
                <img
                  src={project.image}
                  alt={project.title}
                  className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                />
              </div>
              <div className="p-3">
                <h3 className="font-semibold text-sm mb-2">{project.title}</h3>
                <div className="flex flex-wrap gap-1.5">
                  {project.tags.map((tag) => (
                    <span
                      key={tag}
                      className="px-2 py-0.5 text-[10px] font-medium bg-tag-bg text-tag-text rounded-full"
                    >
                      {tag}
                    </span>
                  ))}
                </div>
              </div>
            </a>
          ))}
        </div>
      </div>
    </section>
  );
}