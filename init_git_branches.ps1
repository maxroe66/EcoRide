# Script PowerShell pour initialiser les branches Git du projet EcoRide

# Création de la branche develop à partir de main
git checkout -b develop
git push -u origin develop

# Création de la branche feature/homepage à partir de develop
git checkout -b feature/homepage develop
git push -u origin feature/homepage

# Création de la branche feature/menu à partir de develop
git checkout -b feature/menu develop
git push -u origin feature/menu

# Création de la branche feature/covoiturages-view à partir de develop
git checkout -b feature/covoiturages-view develop
git push -u origin feature/covoiturages-view

# Création de la branche feature/covoiturages-filters à partir de develop
git checkout -b feature/covoiturages-filters develop
git push -u origin feature/covoiturages-filters

# Création de la branche feature/covoiturage-detail à partir de develop
git checkout -b feature/covoiturage-detail develop
git push -u origin feature/covoiturage-detail

# Création de la branche feature/join-covoiturage à partir de develop
git checkout -b feature/join-covoiturage develop
git push -u origin feature/join-covoiturage

# Création de la branche feature/signup à partir de develop
git checkout -b feature/signup develop
git push -u origin feature/signup

# Création de la branche feature/user-space à partir de develop
git checkout -b feature/user-space develop
git push -u origin feature/user-space

# Création de la branche feature/create-trip à partir de develop
git checkout -b feature/create-trip develop
git push -u origin feature/create-trip

# Création de la branche feature/trip-history à partir de develop
git checkout -b feature/trip-history develop
git push -u origin feature/trip-history

# Création de la branche feature/start-stop-trip à partir de develop
git checkout -b feature/start-stop-trip develop
git push -u origin feature/start-stop-trip

# Création de la branche feature/employee-space à partir de develop
git checkout -b feature/employee-space develop
git push -u origin feature/employee-space

# Création de la branche feature/admin-space à partir de develop
git checkout -b feature/admin-space develop
git push -u origin feature/admin-space
