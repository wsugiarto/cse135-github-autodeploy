const credentialsData = {
  "instructional-assistant": {
    "title": "Instructional Assistant - Theory of Computation",
    "dates": "Sep 2024-Dec 2024",
    "metaLeft": "University of California San Diego",
    "metaRight": "San Diego, CA",
    "bullets": [
      "Trained and assisted over 150+ students through office hours, class forums, and detailed solution guides.",
      "Efficiently graded and streamlined 150+ submissions and feedback using PrairieLearn and Gradescope.",
      "Collaborated with professors and the team to analyze student feedback to improve future course iterations."
    ]
  },
  "music-ml": {
    "title": "Music Generation Model using Machine Learning",
    "dates": "March 2025-June 2025",
    "metaLeft": "Project",
    "metaRight": "San Diego, CA",
    "bullets": [
      "Led 3 developers to create models to generate pleasant-sounding piano pieces or extend existing pieces.",
      "Trained generative models in Python using Markov chains, single and dual LSTM architectures.",
      "Achieved superior NLL and top-K accuracy scores compared to pretrained research-paper models."
    ]
  },
  "rtr-ratings": {
    "title": "Rating Prediction Model for Fashion Rentals",
    "dates": "Oct 2024-Dec 2024",
    "metaLeft": "Project",
    "metaRight": "San Diego, CA",
    "bullets": [
      "Led 2 developers to create a model to predict user ratings of clothing from RentTheRunway.",
      "Trained machine learning models in Python using concepts from Feature Engineering, Latent Factor Models, Deep Factorization Machines, and Neural Collaborative Filtering.",
      "Achieved a mean squared error of 1.75 on a scale of 0-10 with a final model incorporating sentiment analysis."
    ]
  },
  "electron-diary": {
    "title": "Electron Developer Diary",
    "dates": "March 2024-Sep 2024",
    "metaLeft": "Project",
    "metaRight": "San Diego, CA",
    "bullets": [
      "Collaborated with 10 developers to launch an Electron-wrapped app for tracking development tasks.",
      "Led a sub-team to design (Figma) and build the front end (HTML, CSS, JavaScript), delivering all scheduling and creation features.",
      "Set up a CI/CD pipeline with linters (Mocha), code analysis (Codacy), documentation (JSDocs), unit tests (Jest), and end-to-end tests (WebDriver IO & Puppeteer).",
      "Successfully launched the application with full functionality and an actionable backlog of enhancements."
    ]
  },
  "vg-modding": {
    "title": "Video Game Modding",
    "dates": "Aug 2023-Sep 2023",
    "metaLeft": "Project",
    "metaRight": "San Diego, CA",
    "bullets": [
      "Designed and developed a new category of items inspired by modern and fantasy weaponry in Minecraft.",
      "Implemented new gameplay features and graphics using Java and ForgeMDK, authoring custom classes with Forge libraries.",
      "Enhanced the player experience and received positive community feedback upon release."
    ]
  }
};

localStorage.setItem('credentials', JSON.stringify(credentialsData));