<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "skybuild";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Comprehensive list derived from user input
$items = [
    // 1) CEMENT, CONCRETE, AND AGGREGATES
    "Portland cement (40 kg)", "Portland cement (50 kg)",
    "Blended cement (40 kg)", "Blended cement (50 kg)",
    "Masonry cement (40 kg)", "White cement (40 kg)",
    "Washed sand (cubic meter)", "River sand (cubic meter)", "Plaster sand (cubic meter)",
    "Gravel / crushed stone 3/4 in", "Gravel / crushed stone 1 in",
    "Concrete hollow blocks 4 in (400x200x100mm)", "Concrete hollow blocks 5 in (400x200x125mm)", "Concrete hollow blocks 6 in (400x200x150mm)",
    "Concrete cover blocks 25 mm", "Concrete cover blocks 50 mm",
    
    // 2) REINFORCEMENT STEEL
    "Deformed rebar 10 mm x 6 m", "Deformed rebar 12 mm x 6 m", "Deformed rebar 16 mm x 6 m", "Deformed rebar 20 mm x 6 m",
    "Plain round bar 10 mm x 6 m", "Plain round bar 12 mm x 6 m",
    "Welded wire mesh 50x50mm (1.2x2.4m)", "Welded wire mesh 100x100mm (2.0x3.0m)",
    "Tie wire Gauge 16 (1 kg)", "Tie wire Gauge 16 (25 kg)",
    
    // 3) STRUCTURAL STEEL
    "Angle bar 25 x 25 x 3 mm x 6m", "Angle bar 50 x 50 x 5 mm x 6m",
    "Flat bar 25 x 6 mm x 6m", "Flat bar 50 x 6 mm x 6m",
    "Square bar 10 mm x 6m", "Square bar 12 mm x 6m",
    "C-channel 50 x 25 x 2.0 mm x 6m", "C-channel 100 x 50 x 2.0 mm x 6m",
    "I-beam 150 mm x 6m", "I-beam 200 mm x 6m",
    "H-beam 150 x 150 mm", "H-beam 200 x 200 mm",
    "Steel plate 1.5 mm (1.2x2.4m)", "Steel plate 3 mm (1.2x2.4m)", "Steel plate 6 mm (1.2x2.4m)",
    "Rectangular steel tube 25 x 50 mm (1.5mm) x 6m", "Rectangular steel tube 50 x 100 mm (2.0mm) x 6m",
    "Square steel tube 25 x 25 mm (1.5mm) x 6m", "Square steel tube 50 x 50 mm (2.0mm) x 6m",
    "Round steel tube / pipe 1 in (Sched 40)", "Round steel tube / pipe 2 in (Sched 40)",
    
    // 4) LUMBER AND WOOD
    "Coco lumber 2 x 2 x 10 ft", "Coco lumber 2 x 3 x 10 ft", "Coco lumber 2 x 4 x 10 ft",
    "Ordinary plywood 1/4 in (1.22x2.44m)", "Ordinary plywood 1/2 in (1.22x2.44m)", "Ordinary plywood 3/4 in (1.22x2.44m)",
    "Marine plywood 1/4 in (1.22x2.44m)", "Marine plywood 1/2 in (1.22x2.44m)", "Marine plywood 3/4 in (1.22x2.44m)",
    "Phenolic board 1/2 in (1.22x2.44m)", "Phenolic board 3/4 in (1.22x2.44m)",
    
    // 5) MASONRY MATERIALS
    "Clay brick 200 x 100 x 60 mm", "AAC block 100 mm (600x200mm)", "AAC block 150 mm (600x200mm)",
    
    // 6) FORMWORK MATERIALS
    "Form lumber 2 x 2", "Form lumber 2 x 3", "Form lumber 2 x 4",
    "Steel scaffolding tube 48.3 mm OD x 3m", "H-frame scaffolding 1.2m x 1.7m",
    
    // 7) ROOFING MATERIALS
    "Prepainted rib type roofing 0.40 mm", "Prepainted corrugated roofing 0.40 mm",
    "Corrugated GI sheet 0.40 mm x 8 ft", "Corrugated GI sheet 0.40 mm x 10 ft",
    "Polycarbonate roofing 6 mm (1.22x2.4m)", "Polycarbonate roofing 6 mm (1.22x6.0m)",
    "Foil roof insulation 25 mm", "Fiberglass roof insulation 50 mm",
    "Roof gutter 5 in x 2.4m", "Roof gutter 6 in x 2.4m",
    "Downspout 2 x 3 in x 2.4m", "Downspout 3 x 4 in x 2.4m",
    "C-purlin 100 x 50 x 20 x 2.0 mm x 6m",
    
    // 8) WATERPROOFING
    "Liquid waterproofing (16 L)", "Liquid waterproofing (4 L)",
    "Cementitious waterproofing (25 kg)", "Bituminous membrane 3.0 mm (1x10m)",
    
    // 9) INSULATION
    "Fiberglass insulation 50 mm (24 kg/m3)", "Rockwool insulation 50 mm", "Rigid foam board 50 mm",
    
    // 10) WALLS, PARTITIONS, AND CLADDING
    "Gypsum board regular 9 mm (1.2x2.4m)", "Gypsum board regular 12 mm (1.2x2.4m)",
    "Gypsum board moisture-resistant 12 mm (1.2x2.4m)",
    "Fiber cement board 4.5 mm (1.2x2.4m)", "Fiber cement board 6 mm (1.2x2.4m)",
    "Metal stud 50 mm x 3.0m", "Metal stud 75 mm x 3.0m",
    "Metal track 50 mm x 3.0m", "Metal track 75 mm x 3.0m",
    
    // 11) CEILING MATERIALS
    "Acoustic ceiling tile 600 x 600 x 12 mm", "T-bar exposed grid 24 mm x 3.6m",
    "Furring channel 19 x 50 mm x 5m", "Carrying channel 12 x 38 mm x 5m",
    
    // 12) FLOORING MATERIALS
    "Ceramic tile 300 x 300 mm", "Ceramic tile 600 x 600 mm",
    "Porcelain tile 600 x 600 mm", "Porcelain tile 800 x 800 mm",
    "Vinyl plank 150 x 900 mm (3mm)", "Laminate flooring 8 mm",
    "Tile adhesive (25 kg)", "Tile grout (2 kg)",
    
    // 13) DOORS
    "Flush door 800 x 2100 mm", "Flush door 900 x 2100 mm",
    "Solid wood door 800 x 2100 mm", "Solid wood door 900 x 2100 mm",
    "Steel door fire-rated 900 x 2100 mm",
    
    // 14) WINDOWS AND GLASS
    "Clear float glass 6 mm", "Tempered glass 10 mm",
    
    // 15) PAINTS AND FINISHES
    "Primer paint (16 L)", "Flat latex paint (16 L)", "Semi-gloss latex paint (16 L)",
    "Gloss enamel paint (4 L)", "Elastomeric paint (16 L)",
    "Skim coat (20 kg)",
    
    // 16) PLUMBING MATERIALS
    "PVC pipe Sched 40 - 1/2 in x 3m", "PVC pipe Sched 40 - 3/4 in x 3m", "PVC pipe Sched 40 - 1 in x 3m", "PVC pipe Sched 40 - 2 in x 3m", "PVC pipe Sched 40 - 3 in x 3m", "PVC pipe Sched 40 - 4 in x 3m",
    "uPVC pipe 20 mm", "uPVC pipe 25 mm", "PPR pipe 20 mm", "PPR pipe 25 mm",
    "GI pipe 1/2 in x 6m", "GI pipe 3/4 in x 6m",
    "Gate valve 1/2 in", "Gate valve 3/4 in", "Gate valve 1 in",
    "Water tank 1000 L", "Water closet (300mm rough-in)", "Lavatory 500 mm",
    
    // 17) ELECTRICAL MATERIALS
    "THHN stranded wire 2.0 sq mm (150m)", "THHN stranded wire 3.5 sq mm (150m)", "THHN stranded wire 5.5 sq mm (150m)",
    "PVC electrical conduit 20 mm x 3m", "PVC electrical conduit 25 mm x 3m",
    "Utility box 2 x 4 in", "Junction box octagonal 4 in",
    "Panel board 100 A", "Circuit breaker 20 A", "Circuit breaker 30 A",
    "Convenience outlet 15 A", "Light switch 15 A",
    "LED bulb 9 W", "LED bulb 12 W", "LED downlight 4 in", "LED tube light 4 ft",
    
    // 18) HVAC AND VENTILATION
    "Air-conditioning unit 1.0 HP", "Air-conditioning unit 1.5 HP", "Air-conditioning unit 2.0 HP",
    "Exhaust fan 8 in", "Exhaust fan 10 in",
    
    // 19) FASTENERS AND HARDWARE
    "Common nail 2 in", "Common nail 3 in", "Common nail 4 in",
    "Concrete nail 2 in", "Concrete nail 3 in",
    "Metal screw 1 in", "Gypsum drywall screw 1 1/2 in",
    "Anchor bolt 12 x 150 mm", "Expansion bolt 3/8 in",
    "Door hinge 3 in", "Door hinge 4 in", "Door lockset (60mm backset)"
];

$stmt = $conn->prepare("SELECT id FROM inventory WHERE item_name = ?");
$insert = $conn->prepare("INSERT INTO inventory (item_name, quantity) VALUES (?, 0)");

$added = 0;
foreach ($items as $item) {
    $stmt->bind_param("s", $item);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows == 0) {
        $insert->bind_param("s", $item);
        $insert->execute();
        $added++;
    }
}

echo "Successfully added $added new items to inventory.\n";
$conn->close();
?>
