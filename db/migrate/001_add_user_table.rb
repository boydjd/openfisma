class AddUserTable < ActiveRecord::Migration
  def self.up
    create_table "users", :force => true do |t|
      t.column "name" ,             :string
      t.column "hashed_password" ,  :string
      t.column "salt" ,             :string
      t.column "created_on",        :datetime
      t.column "updated_on",        :datetime
    end
  end

  def self.down
    drop_table :users
  end
end
