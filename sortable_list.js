const sortableList = document.querySelector(".sortable-list");
const items = sortableList.querySelectorAll(".item");




items.forEach(item => {
  item.addEventListener("dragstart", () => {
    // Adding dragging class to an item after a delay
    setTimeout(() => item.classList.add("dragging"), 0);
  });
  // Removing dragging class from the item on the dragend event
  item.addEventListener("dragend", () => {
    item.classList.remove("dragging");
    //console.log ( "item = " + item.id);
    // Getting all items except currently dragging and making an array of them
    let siblings = [...sortableList.querySelectorAll(".item:not(.dragging)")];
    siblingText = siblings.map(e => e.id);
    //    console.log(siblingText);
    
    saveOrder( siblingText);
  });
});

const initSortableList = (e) => {
  e.preventDefault();
  const draggingItem = document.querySelector(".dragging");
  // Getting all items except currently dragging and making an array of them
  let siblings = [...sortableList.querySelectorAll(".item:not(.dragging)")];

  // Finding the sibling after which the dragging item should be placed
  let nextSibling = siblings.find(sibling => {
    return e.clientY <= sibling.offsetTop + sibling.offsetHeight / 2;
  });

  // Inserting the dragging item before the found sibling
  sortableList.insertBefore(draggingItem, nextSibling);
 
}

sortableList.addEventListener("dragover", initSortableList);
sortableList.addEventListener("dragenter", e => e.preventDefault());

function saveOrder(lijst) {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
      if (this.readyState == 4 && this.status == 200) {
        console.log( this.responseText );
      }
    };
    xhttp.open("POST", "save_order_projects.php" , true);
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send("lijst="+lijst);
  
  }